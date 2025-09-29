<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Activity, ActivityClosure, Attendance, Control, Employee};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CheckController extends Controller
{
    /**
     * Listar controles activos o asistencias.
     */
    public function index(Request $request)
    {
        $idsCsv = trim((string) $request->query('activity_ids', ''));
        $ids = collect(explode(',', $idsCsv))
            ->map(fn($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $ids = collect((array) $request->session()->get('check.active_ids', []))
                ->map(fn($v) => (int) $v)
                ->filter()
                ->unique()
                ->values();
        }

        if ($ids->isEmpty()) {
            return view('check.dashboard', [
                'attendances' => collect(),
                'activities'  => collect(),
                'selected'    => collect(),
            ]);
        }

        // Solo P/A/R y control null o activo
        $validIds = Activity::whereIn('id', $ids)
            ->whereIn('states', ['P', 'A', 'R'])
            ->where(function ($q) {
                $q->whereNull('control_id')
                    ->orWhereHas('control', fn($qq) => $qq->where('status', 'active'));
            })
            ->pluck('id')
            ->values();

        if ($validIds->isEmpty()) {
            // Nada válido → limpiar sesión y no mostrar nada
            $request->session()->forget('check.active_ids');
            return view('check.dashboard', [
                'attendances' => collect(),
                'activities'  => collect(),
                'selected'    => collect(),
            ]);
        }

        // Escribe en sesión SOLO lo que realmente vas a mostrar
        $request->session()->put('check.active_ids', $validIds->all());

        $attendances = Attendance::with(['employee.position', 'activity'])
            ->whereIn('activity_id', $validIds)
            ->orderBy('employee_id')
            ->get();

        $activities = Activity::whereIn('id', $validIds)->get();

        return view('check.dashboard', [
            'attendances' => $attendances,
            'activities'  => $activities,
            'selected'    => $validIds,
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
        // 1) Validación: exigir las actividades a preparar
        $data = $request->validate([
            'activity_ids'   => 'required|array|min:1',
            'activity_ids.*' => 'integer|exists:activities,id',
        ]);

        $ids = collect($data['activity_ids'])->map(fn($v) => (int)$v)->unique()->values();

        session(['check.active_ids' => $ids->values()->all()]);

        // 2) Traer SOLO esas actividades (y filtrar por estados exportables)
        $activities = Activity::whereIn('id', $ids)
            ->whereIn('states', ['P', 'A', 'R'])
            ->with('audiences:id')    // solo IDs
            ->get();

        if ($activities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay actividades válidas para preparar.',
            ], 422);
        }

        // 3) Preparar asistencias únicamente para las audiencias de esas actividades
        $rows = [];
        $now  = now();

        foreach ($activities as $activity) {
            $audienceIds = $activity->audiences->pluck('id');

            // Empleados pertenecientes a las audiencias de esta actividad
            $employees = Employee::whereHas('audiences', function ($q) use ($audienceIds) {
                $q->whereIn('audiences.id', $audienceIds);
            })->pluck('id');

            foreach ($employees as $empId) {
                $rows[] = [
                    'activity_id' => $activity->id,
                    'employee_id' => $empId,
                    'attend'      => false,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        // 4) Upsert SOLO sobre esas combinaciones
        if (!empty($rows)) {
            Attendance::upsert(
                $rows,
                ['activity_id', 'employee_id'],
                ['attend', 'updated_at']
            );
        }

        // 5) Guardar en sesión la selección activa 
        session(['check.active_ids' => $ids->all()]);

        return response()->json([
            'success' => true,
            'message' => 'Asistencias preparadas correctamente para las actividades seleccionadas.',
            'activity_ids' => $ids,
        ]);
    }

    public function create()
    {
        $control = Control::where('status', 'active')->first();

        if (!$control) {
            return redirect()
                ->route('employees.index')
                ->with('info', 'No hay un control activo. Exporta desde Empleados para iniciar uno.');
        }

        // Si usas control_id en Activity (lo pegas al crear grupo), saca los IDs así:
        $activityIds = Activity::where('control_id', $control->id)->pluck('id');

        // Si no hay “pegadas”, intenta tomar del query param (?activity_ids=1,2,3)
        if ($activityIds->isEmpty()) {
            $idsCsv = (string) request()->query('activity_ids', '');
            $activityIds = collect(explode(',', $idsCsv))
                ->map(fn($v) => (int) trim($v))
                ->filter()
                ->unique()
                ->values();
        }

        $attendances = Attendance::with(['employee.position', 'activity'])
            ->whereIn('activity_id', $activityIds)
            ->orderBy('employee_id')
            ->get();

        $activities = Activity::whereIn('id', $activityIds)->get();

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
        $data = $request->validate([
            'password'              => ['required', 'current_password'],
            'activity_ids'          => 'required|string',
            'facilitator_signature' => ['required', 'string', 'regex:/^data:image\/png;base64,/'],
        ]);

        $idsFromForm = collect(explode(',', $data['activity_ids']))
            ->map(fn($v) => (int) trim($v))->filter()->unique();

        $sessionIds = collect((array) $request->session()->get('check.active_ids', []))
            ->map(fn($v) => (int) $v)->filter()->unique();

        $ids = $idsFromForm->intersect($sessionIds)->values()->all();
        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No hay coincidencia con la selección activa.',
            ], 422);
        }

        // Intentar usar un control ya asociado a estas actividades
        $controlIdCandidates = Activity::whereIn('id', $ids)->pluck('control_id')->filter()->unique()->values();

        $control = null;
        if ($controlIdCandidates->count() === 1) {
            $maybe = Control::find($controlIdCandidates->first());
            if ($maybe) $control = $maybe;
        }

        // Si no hay o está finalizado, usa el activo más reciente
        if (!$control || $control->status !== 'active') {
            $control = Control::where('status', 'active')->latest('started_at')->first();
        }

        // Si aún no hay, crea uno
        if (!$control) {
            $control = Control::create([
                'status'     => 'active',
                'started_at' => now(),
                'created_by' => Auth::id(),
            ]);
        }

        // Guarda firma
        try {
            [, $content] = explode(',', $data['facilitator_signature'], 2);
            $binary = base64_decode($content);

            $filename = 'facilitators/' . Str::uuid()->toString() . '.png';
            Storage::disk('signatures')->put($filename, $binary);
            $signaturePath = 'signatures/' . $filename;
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error procesando la firma: ' . $e->getMessage()], 500);
        }

        // Asegura vínculo
        Activity::whereIn('id', $ids)->update(['control_id' => $control->id]);

        DB::transaction(function () use ($ids, $signaturePath, $control) {
            Activity::whereIn('id', $ids)->update([
                'states'     => 'E',
                'updated_at' => now(),
            ]);

            foreach ($ids as $activityId) {
                ActivityClosure::updateOrCreate(
                    ['activity_id' => $activityId],
                    [
                        'control_id'                 => $control->id,
                        'facilitator_signature_path' => $signaturePath,
                        'created_by'                 => Auth::id(),
                    ]
                );
            }

            $quedanPendientes = Activity::where('control_id', $control->id)
                ->where('states', '!=', 'E')
                ->exists();

            if (!$quedanPendientes) {
                $control->update([
                    'status'      => 'finalize',
                    'finished_at' => now(),
                ]);
            }
        });

        // limpia sesión
        $request->session()->forget('check.active_ids');

        $sigueActivo = Activity::where('control_id', $control->id)
            ->where('states', '!=', 'E')
            ->exists();

        return response()->json([
            'success' => true,
            'message' => 'Actividades finalizadas. ' . ($sigueActivo
                ? 'El control sigue activo porque hay otras actividades pendientes.'
                : 'El control se cerró correctamente.'),
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
        try {
            $activityId = (int) $request->input('activity_id');

            // 1) Asegura la actividad (para metadata SIEMPRE)
            $activity = Activity::findOrFail($activityId);

            // 2) Trae asistentes marcados SÍ (puede quedar vacío)
            $attendances = Attendance::with(['activity', 'employee.position'])
                ->where('activity_id', $activityId)
                ->where('attend', 1)
                ->get();

            // 3) Mapear asistentes con firma (puede quedar vacío y está bien)
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

            // 4) Metadata SIEMPRE (ya no depende de attendances)
            $estimatedDate = $activity->getRawOriginal('estimated_date') ?? ($activity->estimated_date?->toDateString());
            $activityMeta = [
                'estimated_date'       => $activity->estimated_date,
                'start_time'           => $activity->start_time,
                'end_time'             => $activity->end_time,
                'place'                => $activity->place,
                'facilitator'          => $activity->facilitator,
                'facilitator_document' => $activity->facilitator_document,
            ];

            // 5) Firma facilitador (igual que ya tenías)
            $closure = ActivityClosure::where('activity_id', $activityId)->latest('id')->first();
            $facilitatorSignature = null;
            if ($closure && $closure->facilitator_signature_path) {
                $path = ltrim($closure->facilitator_signature_path, '/');
                $full = storage_path('app/private/' . $path);
                $relative = Str::startsWith($path, 'signatures/')
                    ? Str::after($path, 'signatures/')
                    : $path;

                $content = null;
                if (file_exists($full)) {
                    $content = file_get_contents($full);
                } else {
                    try {
                        if (Storage::disk('signatures')->exists($relative)) {
                            $content = Storage::disk('signatures')->get($relative);
                        }
                    } catch (\Throwable $e) {
                    }
                }

                if (!$content && str_contains($path, 'signatures/facilitator/')) {
                    $alt = str_replace('signatures/facilitator/', 'signatures/facilitators/', $path);
                    $altFull = storage_path('app/private/' . $alt);
                    if (file_exists($altFull)) {
                        $content = file_get_contents($altFull);
                        $closure->facilitator_signature_path = $alt;
                        $closure->save();
                    } else {
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

            // 6) Respuesta JSON (SIEMPRE JSON)
            return response()->json([
                'success'        => true,
                'message'        => $attendances->isEmpty()
                    ? 'No hay asistentes registrados para esta actividad.'
                    : 'OK',
                'attendees'      => $data,                  // puede ir vacío []
                'estimated_date' => $estimatedDate,
                'topic'          => $activity->topic,
                'activity'       => $activityMeta,
                'closure'        => [
                    'facilitator_signature' => $facilitatorSignature,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Actividad no encontrada.',
            ], 404);
        } catch (\Throwable $e) {
            Log::error('printAttendees error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo preparar los datos del PDF.',
            ], 500);
        }
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
