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
    public function index()
    {
        $control = Control::where('status', 'active')->first();

        if (!$control) {
            // No hay control activo → se puede mostrar un mensaje en la vista
            return view('check.dashboard', [
                'control' => null,
                'attendances' => collect(),
            ]);
        }

        // Traer las asistencias relacionadas al control activo,
        // con la información de empleados y actividades
        $attendances = Attendance::with(['employee.position', 'activity'])
            ->where('control_id', $control->id)
            ->get();

        return view('check.dashboard', compact('control', 'attendances'));
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
                    'control_id'  => $control->id,
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
        $data = $request->validate([
            'activity_id'   => 'required|array|min:1',
            'activity_id.*' => 'exists:activities,id',
        ]);

        $activityIds = $data['activity_id'];

        // Reusar control activo o crearlo
        $control = Control::where('status', 'active')->first();
        if (!$control) {
            $control = Control::create([
                'status'     => 'active',
                'started_at' => now(),
                'created_by' => Auth::id(),
            ]);
        }

        // Preparamos filas a upsert
        $employees = Employee::select('id')->get();
        $rows = [];
        $now = now();
        foreach ($activityIds as $activityId) {
            foreach ($employees as $emp) {
                $rows[] = [
                    'control_id'  => $control->id,
                    'activity_id' => $activityId,
                    'employee_id' => $emp->id,
                    'attend'      => false,   // SIEMPRE “No” por defecto
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }
        }

        // Evitar duplicados si exportas varias veces (requiere índice único, ver paso 6)
        Attendance::upsert(
            $rows,
            ['control_id', 'activity_id', 'employee_id'],
            ['attend', 'updated_at']
        );

        return response()->json([
            'success'     => true,
            'message'     => 'Control de asistencia digital exportado correctamente.',
            'control_id'  => $control->id,
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

        // Actualiza todas las asistencias del mismo control y mismo empleado
        Attendance::where('control_id', $attendance->control_id)
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

                Attendance::where('control_id', $att->control_id)
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
        $request->validate([
            'password' => ['required'],
            'start_time'           => ['required', 'date_format:H:i'],
            'end_time'             => ['required', 'date_format:H:i', 'after:start_time'],
            'place'                => ['required', 'string', 'max:190'],
            'facilitator_name'     => ['required', 'string', 'max:190'],
            'facilitator_document' => ['required', 'string', 'max:50'],
            'facilitator_signature' => ['required', 'string', 'regex:/^data:image\/png;base64,/'],
        ]);

        // Verificar contraseña del usuario actual
        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'La contraseña es incorrecta.',
            ], 401);
        }

        // Buscar control activo
        $control = Control::where('status', 'active')->first();

        if (!$control) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un control activo para finalizar.',
            ], 400);
        }

        // Obtener actividades activas desde los attendances
        $activeActivities = Attendance::where('control_id', $control->id)
            ->whereHas('activity', function ($q) {
                $q->where('states', 'E'); // solo exportadas
            })
            ->pluck('activity_id')
            ->unique();

        if ($activeActivities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay actividades exportadas para finalizar.',
            ], 400);
        }

        // Guardar firma si llegó base64
        if ($request->filled('facilitator_signature')) {
            try {
                $signatureData = $request->input('facilitator_signature');

                // Aseguramos que viene en formato base64
                if (str_starts_with($signatureData, 'data:image')) {
                    [$meta, $content] = explode(',', $signatureData, 2);
                    $binary = base64_decode($content);

                    // Generar nombre único
                    $filename = 'facilitators/' . Str::uuid()->toString() . '.png';

                    // Guardar en storage/app/private
                    Storage::disk('signatures')->put($filename, $binary);

                    $signaturePath = 'signatures/' . $filename;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'El formato de la firma no es válido',
                    ], 422);
                }
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error procesando la firma: ' . $e->getMessage(),
                ], 500);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'La firma del facilitador es obligatoria',
            ], 422);
        }

        // Crear un cierre por cada actividad (misma info para todas)
        $today = now()->toDateString();
        foreach ($activeActivities as $activityId) {
            ActivityClosure::updateOrCreate(
                [
                    'activity_id' => $activityId,
                    'control_id'   => $control->id,
                ],
                [
                    'date'                   => $today,
                    'start_time'             => $request->start_time,
                    'end_time'               => $request->end_time,
                    'place'                  => $request->place,
                    'facilitator_name'       => $request->facilitator_name,
                    'facilitator_document'   => $request->facilitator_document,
                    'facilitator_signature_path' => $signaturePath,
                    'created_by'             => Auth::id(),
                ]
            );
        }

        // Cambiar estado a "E"
        Activity::whereIn('id', $activeActivities)->update(['states' => 'E']);

        // Cerrar control
        $pending = Attendance::where('control_id', $control->id)
            ->whereHas('activity', fn($q) => $q->where('states', 'E'))
            ->exists();

        if (!$pending) {
            $control->update([
                'status'      => 'finished',
                'finished_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'El control de asistencia fue finalizado correctamente.',
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
