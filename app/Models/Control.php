<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Control extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'status',
        'started_at',
        'finished_at',
        'created_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    // 🔗 Relaciones
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'attendances')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
