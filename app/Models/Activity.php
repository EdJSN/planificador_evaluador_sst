<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'thematic_axis',
        'topic',
        'objective',
        'place',
        'start_time',
        'end_time',
        'facilitator',
        'facilitator_document',
        'duration',
        'number_participants',
        'estimated_date',
        'evaluation_methods',
        'resources',
        'budget',
        'states',
        'efficacy_evaluation',
        'efficacy_evaluation_date',
        'responsible',
        'observations',
        'coverage',
    ];

    protected $casts = [
        'estimated_date' => 'date:Y-m-d', // Formato para JSON (solo fecha)
        'efficacy_evaluation_date' => 'date:Y-m-d', // Formato para JSON (solo fecha)
    ];

    protected $appends = ['estimated_date_formatted', 'states_label'];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function getEstimatedDateFormattedAttribute()
    {
        if (!$this->estimated_date) return null;
        return $this->estimated_date->format('d/m/Y');
    }

    public function getStatesLabelAttribute()
    {
        $map = [
            'P' => 'Planificado',
            'A' => 'Aplazado',
            'R' => 'Reprogramado',
            'E' => 'Ejecutado',
        ];

        return $map[$this->states] ?? $this->states ?? '';
    }

    public function control()
    {
        return $this->belongsTo(Control::class);
    }

    public function closures()
    {
        return $this->hasMany(ActivityClosure::class);
    }

    public function scopeExportables($q)
    {
        return $q->whereIn('states', ['P', 'A', 'R']);
    }

    public function audiences()
    {
        return $this->belongsToMany(Audience::class, 'activity_audience');
    }

    public function recalcCoverage(): int
    {
        // Participantes esperados
        $total = (int) ($this->number_participants ?? 0);

        // Evitar división por cero: si no hay total, asumimos 0%
        if ($total <= 0) {
            $this->coverage = 0;
            $this->save();
            return 0;
        }

        // Asistentes marcados (attend = 1)
        $attended = Attendance::where('activity_id', $this->id)
            ->where('attend', true)
            ->count();

        // Porcentaje entero (0-100)
        $coverage = (int) round(($attended / $total) * 100, 0);

        // Persistimos en la columna 'coverage'
        $this->coverage = $coverage;
        $this->save();

        return $coverage;
    }

    /**
     * Para mostrar en la tabla de consulta (ej: "50%").
     * Úsalo como: {{ $activity->coverage_label }}
     */
    public function getCoverageLabelAttribute(): string
    {
        $value = $this->coverage;

        // Si quieres que cuando no haya total muestre "—", mantenlo así.
        if ($value === null) {
            return '—';
        }

        // Aseguramos entero y agregamos el símbolo %
        return ((int) $value) . '%';
    }
}
