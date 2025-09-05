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
        'place_time',
        'group_types',
        'facilitators',
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
        'coverage',
        'observations',
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

    public function closure()
    {
        return $this->hasOne(ActivityClosure::class);
    }
}
