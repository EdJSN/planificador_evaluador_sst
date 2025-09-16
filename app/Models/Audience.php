<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Audience extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function activities()
    {
        return $this->belongsToMany(Activity::class, 'activity_audience');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'audience_employee');
    }
}
