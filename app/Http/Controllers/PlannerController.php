<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Activity, Audience, Control};
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
        $audienceOptions = Audience::pluck('name', 'id')->toArray();

        return view('planner.dashboard', compact('activities', 'audienceOptions'));
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
        $audienceOptions = Audience::pluck('name', 'id')->toArray();

        return view('planner.create', compact('audienceOptions', 'defaultAudienceId'));
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
                'place' => 'nullable|string',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i|after:start_time',
                'facilitator' => 'nullable|string',
                'facilitator_document' => 'nullable|string',
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
                'observations' => 'nullable|string',
                'coverage'  => 'nullable|integer',
                'audiences' => 'required|array|min:1',
                'audiences.*' => 'exists:audiences,id',
            ]);

            $activity = Activity::create(collect($validatedData)->except('audiences')->toArray());

            $activity->audiences()->sync($validatedData['audiences']);

            // Devuelve una respuesta JSON de éxito
            return response()->json([
                'message' => 'Actividad creada exitosamente.',
                'activity' => $activity->load('audiences')
            ], 201);
        } catch (ValidationException $e) {
            // Manejo de errores de validación
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
                'place' => 'nullable|string',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i|after:start_time',
                'facilitator' => 'nullable|string',
                'facilitator_document' => 'nullable|string',
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
                'observations' => 'nullable|string',
                'coverage'  => 'nullable|integer',
                'audiences' => 'required|array|min:1',
                'audiences.*' => 'exists:audiences,id',
            ]);

            // Actualizar actividad
            $activity->update(
                collect($validatedData)->except('audiences')->toArray()
            );

            // Sincronizar audiencias
            $activity->audiences()->sync($validatedData['audiences']);

            return response()->json([
                'message' => 'Actividad actualizada exitosamente.',
                'activity' =>  $activity->load('audiences')
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
        $validated = $request->validate([
            'tipo'          => 'required|in:individual,grupo',
            'activity_id'   => 'required_if:tipo,individual|nullable|exists:activities,id',
            'activity_ids'  => 'required_if:tipo,grupo|array',
            'activity_ids.*' => 'exists:activities,id',
        ]);

        if ($validated['tipo'] === 'individual') {
            // No tocamos states ni escribimos control_id (debe ser NULL o quedar como esté si ya era NULL)
            $activity = Activity::findOrFail($validated['activity_id']);

            // IMPORTANTE: no guardes nada si no cambias nada.
            // Retornamos JSON con los IDs que el front usará para /check/prepare
            return response()->json([
                'success'       => true,
                'message'       => 'Actividad preparada en modo individual.',
                'tipo'          => 'individual',
                'activity_ids'  => [$activity->id],
            ]);
        }

        // === GRUPO ===
        // Creamos un control y asignamos su id a las actividades (la "ligadura" del grupo)
        $control = Control::create([
            'status'     => 'active',
            'created_by' => auth()->id(),
            'started_at' => now(),
        ]);

        $ids = array_values($validated['activity_ids']);
        Activity::whereIn('id', $ids)->update(['control_id' => $control->id]);

        return response()->json([
            'success'       => true,
            'message'       => 'Actividades exportadas como grupo.',
            'tipo'          => 'grupo',
            'control_id'    => $control->id,
            'activity_ids'  => $ids,
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
}



