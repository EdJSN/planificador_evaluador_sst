<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Activity, Attendance, Control, Employee};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

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
        $activeActivities = Attendance::with('activity')->pluck('activity_id')->unique();

        if ($activeActivities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay actividades para finalizar.',
            ], 400);
        }

        // Cambiar estado a "E"
        Activity::whereIn('id', $activeActivities)->update(['states' => 'E']);

        // Cerrar control
        $control->update([
            'status'   => 'finished',
            'ended_at' => now(),
        ]);

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
                // agrupamos condiciones para que sean OR entre topic y fecha
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
            ->pluck('activity')   // sacamos los modelos Activity relacionados
            ->filter()            // quitamos posibles nulls si activity fue borrada
            ->unique('id')        // una sola por activity.id
            ->values();           // reindexar

        return response()->json($activities);
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
