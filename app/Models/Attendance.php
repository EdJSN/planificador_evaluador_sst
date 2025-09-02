<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'control_id',
        'activity_id',
        'employee_id',
        'attend',
    ];

    protected $casts = [
        'attend' => 'boolean',
    ];

    public function control()
    {
        return $this->belongsTo(Control::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
