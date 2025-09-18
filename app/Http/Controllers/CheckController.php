<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Activity, ActivityClosure, Attendance, Control, Employee};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CheckController extends Controller
{
    /**
     * Listar controles activos o asistencias.
     */
    public function index(Request $request)
    {
        $idsCsv = (string) $request->query('activity_ids', '');
        $ids = collect(explode(',', $idsCsv))
            ->map(fn($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return view('check.dashboard', [
                'attendances' => collect(),
                'activities'  => collect(),
                'selected'    => collect(),
            ]);
        }

        $attendances = Attendance::with(['employee.position', 'activity'])
            ->whereIn('activity_id', $ids)
            ->orderBy('employee_id')
            ->get();

        $activities = Activity::whereIn('id', $ids)->get();

        return view('check.dashboard', [
            'attendances' => $attendances,
            'activities'  => $activities,
            'selected'    => $ids,
        ]);
    }


    /**
     * Crear un nuevo control de asistencia.
     */
    public function store(Request $request)
    {
        $request->validate([
            'activities' => 'required|array|min:1',
            'activities.*' => 'exists:activities,id',
        ]);

        // Crear el control
        $control = Control::create([
            'status' => 'active',
            'started_at' => now(),
            'created_by' => Auth::id(),
        ]);

        // Pegar las actividades al control recién creado
        Activity::whereIn('id', $request->activities)->update(['control_id' => $control->id]);

        // Obtener todos los empleados
        $employees = Employee::all();

        // Generar asistencias: actividad × empleado
        foreach ($request->activities as $activityId) {
            foreach ($employees as $employee) {
                Attendance::create([
                    'activity_id' => $activityId,
                    'employee_id' => $employee->id,
                    'attend'      => false, // siempre "No" por defecto
                ]);
            }
        }

        // Redirigir al dashboard con las asistencias del nuevo control
        return redirect()->route('check.index')
            ->with('success', 'Se creó un nuevo control de asistencia.');
    }

    public function prepare(Request $request)
    {
        // Buscar actividades que estén en estados exportables (P, A, R)
        $activities = Activity::whereIn('states', ['P', 'A', 'R'])
            ->with('audiences') // importante: traer audiencias vinculadas
            ->get();

        if ($activities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay actividades exportadas para preparar asistencia.',
            ]);
        }

        $rows = [];
        $now = now();

        foreach ($activities as $activity) {
            $audienceIds = $activity->audiences->pluck('id');

            // Buscar empleados que pertenezcan a esas audiencias
            $employees = Employee::whereHas('audiences', function ($q) use ($audienceIds) {
                $q->whereIn('audiences.id', $audienceIds);
            })->get();

            foreach ($employees as $emp) {
                $rows[] = [
                    'activity_id' => $activity->id,
                    'employee_id' => $emp->id,
                    'attend'      => false,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        Attendance::upsert(
            $rows,
            ['activity_id', 'employee_id'],
            ['attend', 'updated_at']
        );

        return response()->json([
            'success' => true,
            'message' => 'Asistencias preparadas correctamente (filtradas por audiencias).',
        ]);
    }

    public function create()
    {
        // Carga el control ACTIVO y sus datos para la vista create
        $control = Control::where('status', 'active')->first();

        if (!$control) {
            // Si no hay control activo, puedes redirigir a empleados o mostrar un aviso
            return redirect()
                ->route('employees.index')
                ->with('info', 'No hay un control activo. Exporta desde Empleados para iniciar uno.');
        }

        $attendances = Attendance::with(['employee.position', 'activity'])
            ->where('control_id', $control->id)
            ->orderBy('employee_id')
            ->get();

        // Lista “bonita” de actividades activas en este control (para mostrar en la vista)
        $activities = Attendance::where('control_id', $control->id)
            ->with('activity:id,topic')
            ->get()
            ->pluck('activity')
            ->unique('id')
            ->values();

        return view('check.create', compact('control', 'attendances', 'activities'));
    }

    public function updateAttendance(Request $request)
    {
        $data = $request->validate([
            'attendance_id' => 'required|exists:attendances,id',
            'attend'        => 'required|boolean',
        ]);

        $attendance = Attendance::findOrFail($data['attendance_id']);

        // Actualiza la asistencia de este empleado para esta actividad
        Attendance::where('activity_id', $attendance->activity_id)
            ->where('employee_id', $attendance->employee_id)
            ->update(['attend' => $data['attend']]);

        // Recalcular coverage de esta actividad
        $activity = Activity::find($attendance->activity_id);
        if ($activity) {
            $activity->recalcCoverage();
        }

        return response()->json([
            'success' => true,
            'message' => 'Asistencia(s) actualizada(s) correctamente.',
        ]);
    }

    public function bulkUpdateAttendance(Request $request)
    {
        // Validamos lo que llega del front
        $data = $request->validate([
            'attendances'          => 'required|array|min:1',
            'attendances.*.id'     => 'required|exists:attendances,id',
            'attendances.*.attend' => 'required|boolean',
            'activity_ids'         => 'required|array|min:1',
            'activity_ids.*'       => 'integer|exists:activities,id',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['attendances'] as $item) {
                $att = Attendance::find($item['id']);
                if (!$att) continue;

                // Aplicar el mismo attend para este empleado en TODAS las actividades del grupo
                Attendance::whereIn('activity_id', $data['activity_ids'])
                    ->where('employee_id', $att->employee_id)
                    ->update(['attend' => (bool) $item['attend']]);
            }

            // Recalcular coverage para cada actividad del grupo 
            $activities = Activity::whereIn('id', $data['activity_ids'])->get();
            foreach ($activities as $activity) {
                $activity->recalcCoverage();
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Asistencias guardadas para todas las actividades.',
        ]);
    }

    public function finalize(Request $request)
    {
        // (1) Validaciones de entrada (igual que ya tenías)
        $data = $request->validate([
            'password'              => ['required'],
            'activity_ids'          => 'required|string', // CSV
            'facilitator_signature' => ['required', 'string', 'regex:/^data:image\/png;base64,/'],
        ]);

        // (2) Validar contraseña del usuario autenticado
        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña es incorrecta.',
            ], 401);
        }

        // (3) Parsear CSV de actividades → colección de enteros únicos
        $activityIds = collect(explode(',', $data['activity_ids']))
            ->map(fn($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        if ($activityIds->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron actividades para finalizar.',
            ], 400);
        }

        // === NUEVO: forzamos array plano para whereIn/foreach
        $ids = $activityIds->all(); // <-- usar $ids en todo lo que sigue

        // (4) Obtener/crear el control activo (tu misma lógica, compactada)
        $control = Control::where('status', 'active')->latest('started_at')->first();

        if (!$control) {
            $controlIds = Activity::whereIn('id', $ids)->pluck('control_id')->filter()->unique()->values();
            if ($controlIds->count() === 1) {
                $maybe = Control::find($controlIds->first());
                if ($maybe && $maybe->status === 'active') {
                    $control = $maybe;
                }
            }
        }

        if (!$control) {
            $control = Control::create([
                'status'     => 'active',
                'started_at' => now(),
                'created_by' => Auth::id(),
            ]);
        }

        // (5) Guardar la firma del facilitador en disco (carpeta plural 'facilitators/')
        try {
            [, $content] = explode(',', $data['facilitator_signature'], 2);
            $binary = base64_decode($content);

            $filename = 'facilitators/' . Str::uuid()->toString() . '.png';
            Storage::disk('signatures')->put($filename, $binary);

            $signaturePath = 'signatures/' . $filename;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error procesando la firma: ' . $e->getMessage(),
            ], 500);
        }

        // Asegurar que TODAS las actividades queden pegadas al control activo
        Activity::whereIn('id', $ids)->update(['control_id' => $control->id]);

        // (6) Cerrar lote en una transacción para consistencia
        DB::transaction(function () use ($ids, $signaturePath, $control) {
            // (6.a) Marcar las actividades como ejecutadas (y tocar updated_at)
            Activity::whereIn('id', $ids)->update([
                'states'     => 'E',
                'updated_at' => now(),   // <-- NUEVO: fuerza “cambio visible”
            ]);

            // (6.b) Crear/actualizar closures con el control_id y la firma
            foreach ($ids as $activityId) {
                ActivityClosure::updateOrCreate(
                    ['activity_id' => $activityId], // activity_id es unique()
                    [
                        'control_id'                 => $control->id,
                        'facilitator_signature_path' => $signaturePath,
                        'created_by'                 => Auth::id(),
                        // Si tienes un campo 'date' en activity_closures, puedes descomentar:
                        // 'date' => now()->toDateString(),
                    ]
                );
            }

            // (6.c) Cerrar el control
            $control->update([
                'status'      => 'finalize',
                'finished_at' => now(),
            ]);
        });

        // (7) Respuesta al frontend
        return response()->json([
            'success' => true,
            'message' => 'Las actividades fueron finalizadas y el control se cerró correctamente.',
        ]);
    }

    public function searchActivities(Request $request)
    {
        $searchRaw = trim((string) $request->input('searchInput', ''));

        // --- Intentar detectar si el usuario ingresó una fecha (d/m/Y o Y-m-d)
        $date = null;
        if ($searchRaw !== '') {
            // Primero intentamos d/m/Y
            try {
                $d = Carbon::createFromFormat('d/m/Y', $searchRaw);
                $date = $d->toDateString(); // 'YYYY-MM-DD'
            } catch (\Exception $e) {
                // Si falla, intentamos Y-m-d
                try {
                    $d2 = Carbon::createFromFormat('Y-m-d', $searchRaw);
                    $date = $d2->toDateString();
                } catch (\Exception $e2) {
                    // no es fecha reconocible -> $date queda null
                }
            }
        }

        // --- Construir consulta sobre attendances (buscamos por actividad relacionada)
        $query = Attendance::with('activity');

        if ($searchRaw !== '') {
            $query->whereHas('activity', function ($q) use ($searchRaw, $date) {
                $q->where(function ($q2) use ($searchRaw, $date) {
                    $q2->where('topic', 'like', "%{$searchRaw}%");
                    if ($date) {
                        $q2->orWhereDate('estimated_date', $date);
                    }
                });
            });
        }

        $attendances = $query->get();

        // --- Mapear a actividades únicas (una fila por actividad)
        $activities = $attendances
            ->pluck('activity')
            ->filter()
            ->unique('id')
            ->values();

        return response()->json($activities);
    }

    public function printAttendees(Request $request)
    {
        $activityId = $request->input('activity_id');

        $attendances = Attendance::with(['activity', 'employee.position'])
            ->where('activity_id', $activityId)
            ->where('attend', 1)
            ->get();

        if ($attendances->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No hay asistentes registrados para esta actividad.',
                'attendees' => [],
                'closure' => null,
            ]);
        }

        // Mapear asistentes con firma en base64 si existe
        $data = $attendances->map(function ($att) {
            $signatureBase64 = null;

            if ($att->employee->file_path && file_exists(storage_path('app/private/signatures/' . $att->employee->file_path))) {
                $file = file_get_contents(storage_path('app/private/signatures/' . $att->employee->file_path));
                $signatureBase64 = 'data:image/png;base64,' . base64_encode($file);
            }
            return [
                'name'      => $att->employee->full_name ?? '',
                'document'  => $att->employee->document ?? '',
                'position'  => $att->employee->position->position ?? '',
                'file_path' => $signatureBase64,
            ];
        });

        // Datos de la actividad
        $activity = optional($attendances->first())->activity;

        // Obtener la fecha estimada
        $estimatedDate = $activity?->getRawOriginal('estimated_date') ?? ($activity?->estimated_date?->toDateString());

        // Traer el cierre más reciente de esta actividad (por si hay varios)
        $closure = ActivityClosure::where('activity_id', $activityId)
            ->latest('id')->first();

        // Firma del facilitador en base64
        // Firma del facilitador en base64 (robusto)
        $facilitatorSignature = null;
        if ($closure && $closure->facilitator_signature_path) {
            $path = ltrim($closure->facilitator_signature_path, '/'); 
            // 1) Ruta absoluta
            $full = storage_path('app/private/' . $path);

            // 2) Path relativo al disk 'signatures' (raíz: storage/app/private/signatures)
            $relative = Str::startsWith($path, 'signatures/')
                ? Str::after($path, 'signatures/')
                : $path;

            $content = null;

            if (file_exists($full)) {
                $content = file_get_contents($full);
            } else {
                // 3) Intenta por el disk (más confiable si el absolute falla por permisos/OS)
                try {
                    if (Storage::disk('signatures')->exists($relative)) {
                        $content = Storage::disk('signatures')->get($relative);
                    }
                } catch (\Throwable $e) {
                    // silencio, probamos fallback
                }
            }

            // 4) Fallback singular → plural (histórico)
            if (!$content && str_contains($path, 'signatures/facilitator/')) {
                $alt = str_replace('signatures/facilitator/', 'signatures/facilitators/', $path);
                $altFull = storage_path('app/private/' . $alt);
                if (file_exists($altFull)) {
                    $content = file_get_contents($altFull);

                    // (opcional) Corrige la ruta en BD para que no vuelva a fallar
                    $closure->facilitator_signature_path = $alt;
                    $closure->save();
                } else {
                    // También intenta por disk
                    $altRel = Str::after($alt, 'signatures/');
                    if (Storage::disk('signatures')->exists($altRel)) {
                        $content = Storage::disk('signatures')->get($altRel);
                        $closure->facilitator_signature_path = $alt;
                        $closure->save();
                    }
                }
            }

            if (!empty($content)) {
                $facilitatorSignature = 'data:image/png;base64,' . base64_encode($content);
            }
        }

        $activity = optional($attendances->first())->activity;
        $activityMeta = [
            'estimated_date'       => $activity->estimated_date,       
            'start_time'           => $activity->start_time,           
            'end_time'             => $activity->end_time,              
            'place'                => $activity->place,
            'facilitator'          => $activity->facilitator,          
            'facilitator_document' => $activity->facilitator_document,
        ];

        // Respuesta JSON final
        return response()->json([
            'attendees'      => $data,
            'estimated_date' => $estimatedDate,
            'topic'          => $activity?->topic,
            'activity'       => $activityMeta,

            // 'closure' solo sigue aportando la firma (los demás campos no existen en esa tabla)
            'closure' => [
                'facilitator_signature' => $facilitatorSignature,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
