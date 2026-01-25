<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'attendance_request_id',
        'approved_by',
        'approved_at',
        'status',
        'final_started_at',
        'final_ended_at',
        'final_break_minutes',
        'final_work_minutes',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
