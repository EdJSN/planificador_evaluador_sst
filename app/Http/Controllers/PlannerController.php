<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Activity, Audience, Control};
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PlannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct()
    {
        $this->middleware('permission:view_activity')->only(['dashboard', 'index', 'show']);
        $this->middleware('permission:create_activity')->only(['store', 'create']);
        $this->middleware('permission:edit_activity')->only(['edit', 'update']);
        $this->middleware('permission:delete_activity')->only(['destroy']);
        $this->middleware('permission:export_activity')->only(['export']);
    }

    public function dashboard(Request $request)
    {
        // Tamaño de página (default 25)
        $perPage = $request->integer('per_page', 25);

        // Años disponibles (distintos, descendente)
        $years = Activity::whereNotNull('estimated_date')
            ->selectRaw('DISTINCT YEAR(estimated_date) as yr')
            ->orderByDesc('yr')
            ->pluck('yr');

        // Año por defecto (el más reciente o el año actual si no hay datos)
        $fallbackYear = $years->first() ?? now()->year;
        $yearToShow   = (int) $request->query('year', $fallbackYear);

        // Consulta a base de actividades + filtro por año 
        $activities = Activity::with(['audiences'])
            ->withCount(['attendances as executed_count' => function ($q) {
                $q->where('attend', true);
            }])
            ->when($yearToShow, fn($q) => $q->whereYear('estimated_date', $yearToShow))
            ->orderByDesc('estimated_date')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString(); 

        // Opciones de audiencias para la vista
        $audienceOptions = Audience::pluck('name', 'id')->toArray();

        // Totales del año seleccionado (required/executed/%)
        $required = (int) Activity::whereYear('estimated_date', $yearToShow)
            ->sum(DB::raw('COALESCE(number_participants,0)'));

        $executed = (int) DB::table('attendances')
            ->join('activities', 'attendances.activity_id', '=', 'activities.id')
            ->whereYear('activities.estimated_date', $yearToShow)
            ->whereRaw("TRIM(activities.states) = 'E'")
            ->where('attendances.attend', 1)
            ->count();

        $summaryTotals = [
            'required' => $required,
            'executed' => $executed,
            'pct'      => $required > 0 ? (int) round(($executed / $required) * 100) : null,
        ];

        return view('planner.dashboard', [
            'activities'      => $activities,     
            'audienceOptions' => $audienceOptions,
            'years'           => $years,
            'yearToShow'      => $yearToShow,
            'summaryTotals'   => $summaryTotals,
        ]);
    }

    public function index(Request $request)
    {
        // 1) Años disponibles tomados desde estimated_date
        $years = Activity::query()
            ->whereNotNull('estimated_date')
            ->selectRaw('DISTINCT YEAR(estimated_date) as yr')
            ->orderByDesc('yr')
            ->pluck('yr')
            ->map(fn($y) => (int) $y);

        // Si aún no hay actividades con fecha, usa el año actual
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        // 2) Año seleccionado (por query ?year=YYYY)
        $defaultYear = (int) ($years->first() ?? now()->year);
        $yearToShow  = (int) $request->input('year', $defaultYear);

        // 3) Actividades para la tabla (FILTRADAS por el año)
        $start = sprintf('%04d-01-01', $yearToShow);
        $end   = sprintf('%04d-12-31', $yearToShow);

        $activities = Activity::with('audiences')
            ->withCount(['attendances as executed_count' => function ($q) {
                $q->where('attend', true);
            }])
            ->whereBetween('estimated_date', [$start, $end])  // <— cambio clave
            ->orderByDesc('id')
            ->get();

        // 4) Cobertura del año (solo actividades en estado 'E')
        $required = Activity::query()
            ->whereYear('estimated_date', $yearToShow)
            ->whereRaw("TRIM(states) = 'E'")
            ->sum(DB::raw('COALESCE(number_participants,0)'));

        $executed = DB::table('attendances')
            ->join('activities', 'attendances.activity_id', '=', 'activities.id')
            ->whereYear('activities.estimated_date', $yearToShow)
            ->whereRaw("TRIM(activities.states) = 'E'")
            ->where('attendances.attend', 1)
            ->count();

        $summaryTotals = [
            'required' => (int) $required,
            'executed' => (int) $executed,
            'pct'      => $required > 0 ? (int) round(($executed / $required) * 100) : null,
        ];

        // 5) Render
        return view('planner.index', [
            'activities'    => $activities,
            'years'         => $years,
            'yearToShow'    => $yearToShow,
            'summaryTotals' => $summaryTotals,
        ]);
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
            Log::error('Error al crear actividad: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['message' => 'Ocurrió un error interno del servidor al crear la actividad.'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Activity $activity) 
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
            Log::error('Error al actualizar actividad: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
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
            $activity = Activity::findOrFail($validated['activity_id']);

            // Retornar JSON con los IDs que el front usará para /check/prepare
            return response()->json([
                'success'       => true,
                'message'       => 'Actividad preparada en modo individual.',
                'tipo'          => 'individual',
                'activity_ids'  => [$activity->id],
            ]);
        }

        // Grupo
        // Crear un control y asignar su id a las actividades
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
