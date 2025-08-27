<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Activity, Attendance, Control, Employee};
use Illuminate\Support\Facades\Auth;

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

    public function export(Request $request)
    {
        $request->validate([
            'activity_id' => 'required|array|min:1',
            'activity_id.*' => 'exists:activities,id',
        ]);

        // Crear un nuevo control activo
        $control = Control::create([
            'status' => 'active',
            'created_by' => auth()->id(),
        ]);

        // Obtener todos los empleados
        $employees = Employee::all();

        // Crear attendances para cada actividad y empleado
        foreach ($request->activity_id as $activityId) {
            foreach ($employees as $employee) {
                Attendance::create([
                    'control_id' => $control->id,
                    'activity_id' => $activityId,
                    'employee_id' => $employee->id,
                    'attend' => false, // inicia en "NO"
                ]);
            }
        }

        return redirect()
            ->route('check.create') // redirige a la vista del control activo
            ->with('success', 'Lista exportada correctamente.');
    }


    public function create()
    {
        //
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
