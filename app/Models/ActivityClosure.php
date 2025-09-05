<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityClosure extends Model
{
    protected $fillable = [
        'activity_id',
        'control_id',
        'start_time',
        'end_time',
        'place',
        'facilitator_name',
        'facilitator_document',
        'facilitator_signature_path',
        'created_by'
    ];

    public function activity()
    { 
        return $this->belongsTo(Activity::class); 
    }
}

