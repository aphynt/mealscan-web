<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'nik',
        'name',
        'photo_path',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getPhotoUrlAttribute()
    {
        if ($this->photo_path && \Storage::disk('public')->exists($this->photo_path)) {
            return \Storage::disk('public')->url($this->photo_path);
        }
        return null;
    }

    public function faceEmbedding(): HasOne
    {
        return $this->hasOne(FaceEmbedding::class, 'nik', 'nik');
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class, 'nik', 'nik');
    }
    
    public function hasFaceRegistered(): bool
    {
        return $this->faceEmbedding()->exists();
    }
}
