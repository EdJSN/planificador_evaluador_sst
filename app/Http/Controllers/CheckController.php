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

        return response()->json([
            'success' => true,
            'message' => 'Asistencia(s) actualizada(s) correctamente.',
        ]);
    }

    public function bulkUpdateAttendance(Request $request)
    {
        $data = $request->validate([
            'attendances'               => 'required|array|min:1',
            'attendances.*.id'          => 'required|exists:attendances,id',
            'attendances.*.attend'      => 'required|boolean',
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['attendances'] as $item) {
                $att = Attendance::find($item['id']);
                if (!$att) continue;

                Attendance::where('activity_id', $att->activity_id)
                    ->where('employee_id', $att->employee_id)
                    ->update(['attend' => $item['attend']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Asistencias guardadas en lote correctamente.',
        ]);
    }

    public function finalize(Request $request)
    {
        $data = $request->validate([
            'password'              => ['required'],
            'activity_ids'          => 'required|string',
            'facilitator_signature' => ['required', 'string', 'regex:/^data:image\/png;base64,/'],
        ]);

        // Validar contraseña
        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña es incorrecta.',
            ], 401);
        }

        // Convertir CSV a array
        $activityIds = collect(explode(',', $data['activity_ids']))
            ->map(fn($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->toArray();

        if (empty($activityIds)) {
            return response()->json([
                'success' => false,
                'message' => 'No se recibieron actividades para finalizar.',
            ], 400);
        }

        // Guardar firma
        try {
            [$meta, $content] = explode(',', $data['facilitator_signature'], 2);
            $binary = base64_decode($content);

            $filename = 'facilitator/' . Str::uuid()->toString() . '.png';
            Storage::disk('signatures')->put($filename, $binary);

            $signaturePath = 'signatures/' . $filename;
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error procesando la firma: ' . $e->getMessage(),
            ], 500);
        }

        // Actualizar actividades: marcar como "E" (ejecutadas)
        Activity::whereIn('id', $activityIds)
            ->update(['states' => 'E']);

        // Actualizar o crear cierres (solo guardan firma + auditoría)
        $today = now()->toDateString();
        foreach ($activityIds as $activityId) {
            ActivityClosure::updateOrCreate(
                ['activity_id' => $activityId],
                [
                    'date'                      => $today,
                    'facilitator_signature_path' => $signaturePath,
                    'created_by'                => Auth::id(),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Las actividades fueron finalizadas correctamente.',
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

        // Obtener la fecha estimada
        $estimatedDate = optional($attendances->first()->activity)->estimated_date;

        // Datos de la actividad
        $activity = optional($attendances->first())->activity;

        // Traer el cierre más reciente de esta actividad (por si hay varios)
        $closure = ActivityClosure::where('activity_id', $activityId)
            ->latest('id')->first();

        // Firma del facilitador en base64
        $facilitatorSignature = null;
        if ($closure && $closure->facilitator_signature_path) {
            $full = storage_path('app/private/' . $closure->facilitator_signature_path);
            if (file_exists($full)) {
                $facilitatorSignature = 'data:image/png;base64,' . base64_encode(file_get_contents($full));
            }
        }

        return response()->json([
            'attendees'      => $data,
            'estimated_date' => $estimatedDate,
            'topic'          => optional($attendances->first()->activity)->topic,
            'closure' => $closure ? [
                'date'                  => $closure->date,
                'start_time'            => $closure->start_time,
                'end_time'              => $closure->end_time,
                'place'                 => $closure->place,
                'facilitator_name'      => $closure->facilitator_name,
                'facilitator_document'  => $closure->facilitator_document,
                'facilitator_signature' => $facilitatorSignature,
            ] : null,
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
