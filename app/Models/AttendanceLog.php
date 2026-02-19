<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    protected $fillable = [
        'nik',
        'meal_type',
        'status',
        'quantity',
        'rating',
        'sys_post',
        'remarks',
        'created_by',
        'attendance_date',
        'attendance_time',
        'similarity_score',
        'confidence_score',
        'is_real_face',
        'photo_path',
    ];
    protected $casts = [
        'attendance_date' => 'date',
        'attendance_time' => 'datetime',
        'similarity_score' => 'float',
        'confidence_score' => 'float',
        'is_real_face' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'nik', 'nik');
    }
}
