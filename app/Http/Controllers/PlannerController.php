<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Activity, Control};
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PlannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function dashboard()
    {
        $activities = Activity::orderBy('id', 'desc')->get();
        return view('planner.dashboard', compact('activities'));
    }

    public function index()
    {
        $activities = Activity::all();
        return view('planner.index', compact('activities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('planner.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'thematic_axis' => 'required|string|max:500',
                'topic' => 'required|string|max:500',
                'objective' => 'required|string|max:500',
                'place_time' => 'nullable|string',
                'group_types' => 'nullable|string',
                'facilitators' => 'nullable|string',
                'duration' => 'nullable|numeric|decimal:0,2',
                'number_participants' => 'nullable|integer',
                'estimated_date' => 'required|date',
                'evaluation_methods' => 'nullable|string',
                'resources' => 'nullable|string',
                'budget' => 'nullable|string',
                'states' => 'required|string|max:1',
                'efficacy_evaluation'  => 'nullable|string',
                'efficacy_evaluation_date' => 'nullable|date',
                'responsible' => 'nullable|string',
                'coverage'  => 'nullable|integer',
                'observations' => 'nullable|string',
            ]);

            $activity = Activity::create($validatedData);

            // En lugar de redirigir, devuelve una respuesta JSON de éxito
            return response()->json([
                'message' => 'Actividad creada exitosamente.',
                'activity' => $activity
            ], 201);
        } catch (ValidationException $e) {
            // Manejo de errores de validación, Laravel ya lo convierte a JSON 422 si es una solicitud AJAX
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Captura cualquier otro error inesperado en el servidor
            \Log::error('Error al crear actividad: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Ocurrió un error interno del servidor al crear la actividad.'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Activity $activity) // Asegúrate de que Activity $activity esté aquí
    {
        try {
            $validatedData = $request->validate([
                'thematic_axis' => 'required|string|max:500',
                'topic' => 'required|string|max:500',
                'objective' => 'required|string|max:500',
                'place_time' => 'nullable|string',
                'group_types' => 'nullable|string',
                'facilitators' => 'nullable|string',
                'duration' => 'nullable|numeric|decimal:0,2',
                'number_participants' => 'nullable|integer',
                'estimated_date' => 'required|date',
                'evaluation_methods' => 'nullable|string',
                'resources' => 'nullable|string',
                'budget' => 'nullable|string',
                'states' => 'required|string|max:1',
                'efficacy_evaluation'  => 'nullable|string',
                'efficacy_evaluation_date' => 'nullable|date',
                'responsible' => 'nullable|string',
                'coverage'  => 'nullable|integer',
                'observations' => 'nullable|string',
            ]);

            // Usa $validatedData para la actualización para mayor seguridad
            $activity->update($validatedData);

            return response()->json([
                'message' => 'Actividad actualizada exitosamente.',
                'activity' => $activity
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar actividad: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Ocurrió un error interno del servidor al actualizar la actividad.'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Activity $activity)
    {
        // 1. Validar la contraseña
        $request->validate([
            'password' => 'required',
        ]);

        // Verificar si la contraseña ingresada coincide con la del usuario autenticado
        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json(['message' => 'Contraseña incorrecta. Por favor, inténtalo de nuevo.'], 403);
        }

        // 2. Eliminar la actividad (soft delete)
        try {
            $activity->delete();
            return response()->json(['message' => 'Actividad eliminada exitosamente.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al eliminar la actividad: ' . $e->getMessage()], 500);
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:individual,grupo',
            'activity_id' => 'required_if:tipo,individual|nullable|exists:activities,id',
            'activity_ids' => 'required_if:tipo,grupo|array',
            'activity_ids.*' => 'exists:activities,id',
        ]);

        if ($request->tipo === 'individual') {
            // Exportación individual (sin control_id)
            $activity = Activity::findOrFail($request->activity_id);
            $activity->control_id = null;
            $activity->save();

            return back()->with('success', 'Actividad exportada individualmente.');
        }

        if ($request->tipo === 'grupo') {
            // Crear control y asignar a varias actividades
            $control = Control::create([
                'status' => 'activo',
                'created_by' => auth()->id(),
            ]);

            Activity::whereIn('id', $request->activity_ids)->update(['control_id' => $control->id]);

            return back()->with('success', 'Actividades exportadas como grupo.');
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
}
