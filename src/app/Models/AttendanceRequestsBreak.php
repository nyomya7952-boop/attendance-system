<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequestsBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_request_id',
        'break_start_at',
        'break_end_at',
    ];

    public function attendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }
}
