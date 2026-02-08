<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'requested_work_date',
        'requested_started_at',
        'requested_ended_at',
        'remarks',
        'requested_by',
        'approver_id',
        'status',
    ];

    protected $casts = [
        'requested_work_date' => 'date',
        'requested_started_at' => 'datetime',
        'requested_ended_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function attendanceRequestBreaks()
    {
        return $this->hasMany(AttendanceRequestBreak::class);
    }
}
