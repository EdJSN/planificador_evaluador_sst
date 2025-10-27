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
    public function dashboard(Request $request)
    {
        // 1) Actividades base
        $activities = Activity::with('audiences')
            ->withCount(['attendances as executed_count' => function ($q) {
                $q->where('attend', true);
            }])
            ->orderByDesc('id')
            ->get();

        $audienceOptions = Audience::pluck('name', 'id')->toArray();

        // IDs mostrados en la tabla
        $activityIds = $activities->pluck('id');

        // 2) Totales por año
        $requiredPerYear = Activity::query()
            ->whereIn('id', $activityIds)
            ->whereNotNull('estimated_date')
            ->whereRaw("TRIM(states) = 'E'")
            ->whereNull('deleted_at')
            ->selectRaw('YEAR(estimated_date) as yr, COALESCE(SUM(number_participants),0) as required')
            ->groupBy('yr');

        $executedPerYear = DB::table('attendances')
            ->join('activities', 'attendances.activity_id', '=', 'activities.id')
            ->whereIn('attendances.activity_id', $activityIds)
            ->whereNotNull('activities.estimated_date')
            ->whereRaw("TRIM(activities.states) = 'E'")
            ->where('attendances.attend', 1)
            ->selectRaw('YEAR(activities.estimated_date) as yr, COUNT(*) as executed')
            ->groupBy('yr');

        $rows = DB::query()
            ->fromSub($requiredPerYear, 'r')
            ->leftJoinSub($executedPerYear, 'e', 'e.yr', '=', 'r.yr')
            ->selectRaw('r.yr, r.required, COALESCE(e.executed,0) as executed')
            ->orderByDesc('r.yr')
            ->get();

        $totalsByYear = $rows->mapWithKeys(function ($r) {
            $req = (int) $r->required;
            $exe = (int) $r->executed;
            $pct = $req > 0 ? (int) round(($exe / $req) * 100) : null;
            return [(string) $r->yr => ['required' => $req, 'executed' => $exe, 'pct' => $pct]];
        });

        // 3) Selección de año
        $years        = $totalsByYear->keys()->values();
        $fallbackYear = optional($activities->max('estimated_date'))->format('Y') ?? now()->format('Y');
        $yearToShow   = (int) $request->query('year', $years->first() ?? $fallbackYear);

        // 4) Filtrado por año
        // Solo mostrar en la tabla las actividades que correspondan al año seleccionado
        $activities = $activities->filter(function ($activity) use ($yearToShow) {
            if (!$activity->estimated_date) return false;
            return (int) \Carbon\Carbon::parse($activity->estimated_date)->format('Y') === (int) $yearToShow;
        });

        // 5) Totales del año seleccionado
        $summaryTotals = $totalsByYear[$yearToShow] ?? ['required' => 0, 'executed' => 0, 'pct' => null];

        // 6) Retorno de vista
        return view('planner.dashboard', [
            'activities'      => $activities,
            'audienceOptions' => $audienceOptions,
            'totalsByYear'    => $totalsByYear,
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

        // 2) Año seleccionado (por query ?year=YYYY) con fallback
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
