<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'started_at',
        'ended_at',
        'status',
        'total_break_minutes',
        'total_work_minutes',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attendance) {
            $exists = static::where('user_id', $attendance->user_id)
                ->where('work_date', $attendance->work_date)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'work_date' => ['この日付の勤怠記録は既に存在します。'],
                ]);
            }
        });

        static::updating(function ($attendance) {
            $exists = static::where('user_id', $attendance->user_id)
                ->where('work_date', $attendance->work_date)
                ->where('id', '!=', $attendance->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'work_date' => ['この日付の勤怠記録は既に存在します。'],
                ]);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceBreak()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function attendanceRequest()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function attendanceApproval()
    {
        return $this->hasMany(AttendanceApproval::class);
    }
}
