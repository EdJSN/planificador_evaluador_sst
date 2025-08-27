<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function getFullNameAttribute()
    {
        return "{$this->names} {$this->lastname1} {$this->lastname2}";
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    //Helper para generar una URL segura y temporal a la firma
    public function getSignatureUrlAttribute()
    {
        return $this->file_path
            ? Storage::disk('private')->url($this->file_path)
            : null;
    }

   //Helper para eliminar firmas
    public function deleteSignature()
    {
        if ($this->file_path && Storage::disk('private')->exists($this->file_path)) {
            Storage::disk('private')->delete($this->file_path);
        }
    }
}
