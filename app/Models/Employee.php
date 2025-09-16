<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'names',
        'lastname1',
        'lastname2',
        'document',
        'position_id',
        'file_path',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    // Un empleado pertenece a un cargo/posición
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    // Un empleado puede estar en muchos checks (asistencia)
    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function audiences()
    {
        return $this->belongsToMany(Audience::class, 'audience_employee');
    }

    /*
    |--------------------------------------------------------------------------
    | Accesores
    |--------------------------------------------------------------------------
    */

    // Nombre completo concatenado
    public function getFullNameAttribute()
    {
        return "{$this->names} {$this->lastname1} {$this->lastname2}";
    }
}
