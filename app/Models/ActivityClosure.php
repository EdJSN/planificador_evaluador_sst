<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityClosure extends Model
{
    protected $fillable = [
        'activity_id',
        'control_id',
        'facilitator_signature_path',
        'created_by'
    ];

    public function control()
    {
        return $this->belongsTo(Control::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
