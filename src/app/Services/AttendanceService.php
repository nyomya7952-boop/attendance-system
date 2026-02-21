<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Enums\AttendanceStatus;

class AttendanceService
{
    /**
     * 出勤処理
     */
    public function clockIn(Request $request)
    {
        try {
            $attendance = Attendance::create([
                'user_id' => auth()->user()->id,
                'work_date' => now()->format('Y-m-d'),
                'started_at' => now(),
                'status' => AttendanceStatus::CLOCKED_IN,
            ]);

            return redirect()->route('attendance.index')
                ->with('message', '出勤しました。');
        } catch (QueryException $e) {
            // Unique制約違反の場合
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();

            if ($errorCode == 1062 || $errorCode == 23000 || str_contains($errorMessage, 'Duplicate entry') || str_contains($errorMessage, 'UNIQUE constraint')) {
                return redirect()->route('attendance.index')
                    ->with('error', '既に出勤済みです。');
            }

            return redirect()->route('attendance.index')
                ->with('error', '出勤登録に失敗しました。');
        }
    }

    /**
     * 退勤処理
     */
    public function clockOut(Request $request)
    {
        try {
            $attendance = Attendance::where('user_id', auth()->user()->id)
                ->where('work_date', now()->format('Y-m-d'))
                ->first();

            if (!$attendance) {
                return redirect()->route('attendance.index')
                    ->with('error', '出勤記録が見つかりません。');
            }

            if ($attendance->ended_at) {
                return redirect()->route('attendance.index')
                    ->with('error', '既に退勤済みです。');
            }

            if ($attendance->attendanceBreak()->whereNull('break_end_at')->exists()) {
                return redirect()->route('attendance.index')
                    ->with('error', '休憩中のため退勤できません。');
            }

            $attendance->ended_at = now();
            $attendance->status = AttendanceStatus::CLOCKED_OUT;

            // breakTimeを計算（終了済みの休憩のみ）
            $totalBreakMinutes = $attendance->attendanceBreak()
                ->whereNotNull('break_end_at')
                ->get()
                ->sum(function ($break) {
                    return $break->break_end_at->diffInMinutes($break->break_start_at);
                });

            // 合計勤務時間を計算（退勤済みの場合のみ）
            $totalWorkMinutes = max(
                0,
                $attendance->ended_at->diffInMinutes($attendance->started_at) - $totalBreakMinutes
            );

            $attendance->total_break_minutes = $totalBreakMinutes;
            $attendance->total_work_minutes = $totalWorkMinutes;
            $attendance->save();
            return redirect()->route('attendance.index')
                ->with('message', '退勤しました。');
        } catch (QueryException $e) {
            return redirect()->route('attendance.index')
                ->with('error', '退勤登録に失敗しました。');
        }
    }

    /**
     * 休憩開始処理
     */
    public function breakStart(Request $request)
    {
        try {
            $attendance = Attendance::where('user_id', auth()->user()->id)
                ->where('work_date', now()->format('Y-m-d'))
                ->first();

            if (!$attendance) {
                return redirect()->route('attendance.index')
                    ->with('error', '出勤記録が見つかりません。');
            }

            $attendanceBreak = $attendance->attendanceBreak()->create([
                'break_start_at' => now(),
                'status' => AttendanceStatus::ON_BREAK,
            ]);

            return redirect()->route('attendance.index')
                ->with('message', '休憩を開始しました。');
        } catch (QueryException $e) {
            return redirect()->route('attendance.index')
                ->with('error', '休憩開始登録に失敗しました。');
        }
    }

    /**
     * 休憩終了処理
     */
    public function breakEnd(Request $request)
    {
        try {
            $attendance = Attendance::where('user_id', auth()->user()->id)
                ->where('work_date', now()->format('Y-m-d'))
                ->first();

            if (!$attendance) {
                return redirect()->route('attendance.index')
                    ->with('error', '出勤記録が見つかりません。');
            }

            if ($attendance->ended_at) {
                return redirect()->route('attendance.index')
                    ->with('error', '既に退勤済みです。');
            }

            $openBreaks = $attendance->attendanceBreak()
                ->whereNull('break_end_at')
                ->orderBy('break_start_at', 'desc')
                ->get();

            if ($openBreaks->count() > 1) {
                return redirect()->route('attendance.index')
                    ->with('error', '休憩記録が複数残っています。');
            }

            $attendanceBreak = $openBreaks->first();

            if (!$attendanceBreak) {
                return redirect()->route('attendance.index')
                    ->with('error', '休憩中の記録が見つかりません。');
            }

            // 休憩終了時刻を設定
            $attendanceBreak->break_end_at = now();
            $attendance->status = AttendanceStatus::CLOCKED_IN;
            $attendanceBreak->save();

            // 休憩合計時間を更新（終了済みの休憩のみ集計）
            $totalBreakMinutes = $attendance->attendanceBreak()
                ->whereNotNull('break_end_at')
                ->get()
                ->sum(function ($break) {
                    return $break->break_end_at->diffInMinutes($break->break_start_at);
                });
            $attendance->total_break_minutes = $totalBreakMinutes;
            $attendance->save();

            return redirect()->route('attendance.index')
                ->with('message', '休憩を終了しました。');
        } catch (QueryException $e) {
            return redirect()->route('attendance.index')
                ->with('error', '休憩終了登録に失敗しました。');
        }
    }
}

