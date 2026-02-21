<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\User;

class AttendanceController extends Controller
{
    /**
     * 勤怠一覧画面を表示（管理者）
     */
    public function index(Request $request)
    {
        $currentMonth = $request->get('date', now()->format('Y-m-d'));
        $date = \Carbon\Carbon::parse($currentMonth);

        // 前日・翌日の取得
        $prevDay = $date->copy()->subDay()->format('Y-m-d');
        $nextDay = $date->copy()->addDay()->format('Y-m-d');

        // 全ユーザの勤怠データを取得
        $attendances = User::leftJoin('attendances', function ($join) use ($date) {
            $join->on('attendances.user_id', '=', 'users.id')
                ->whereDate('attendances.work_date', $date->format('Y-m-d'));
        })
            ->select('users.*', 'attendances.*', 'users.id as user_id', 'attendances.id as attendance_id')
            ->orderBy('users.id', 'asc')
            ->orderBy('users.role_id', 'desc')
            ->get();

        return view('admin.attendance-list', [
            'currentDate' => $date->format('Y年m月d日'),
            'currentDateShort' => $date->format('Y/m/d'),
            'prevDay' => $prevDay,
            'nextDay' => $nextDay,
            'attendances' => $attendances,
        ]);
    }

    /**
     * 勤怠詳細画面を表示（管理者）
     */
    public function show($id)
    {
        // リクエストパラメーターに紐づく勤怠データを取得
        $attendance = Attendance::with('attendanceBreak')->findOrFail($id);
        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)->first();
        return view('admin.attendance-show', [
            'attendance' => $attendance,
            'attendanceBreaks' => $attendance->attendanceBreak,
            'attendanceRequest' => $attendanceRequest,
        ]);
    }

    /**
     * 勤怠詳細更新処理（管理者）
     */
    public function update(AttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // 申請済みの場合はエラーを表示
        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)->first();
        if ($attendanceRequest) {
            return redirect()->route('attendance.detail', $id)->with('error', '申請済みのため修正できません。');
        }

        $workDate = $attendance->work_date ? $attendance->work_date->toDateString() : now()->toDateString();
        $startedAtInput = $request->input('started_at');
        $endedAtInput = $request->input('ended_at');

        // 勤怠データを更新
        $attendance->started_at = $startedAtInput ? \Illuminate\Support\Carbon::parse($workDate)->setTimeFromTimeString($startedAtInput)->format('Y-m-d H:i:s') : null;
        $attendance->ended_at = $endedAtInput ? \Illuminate\Support\Carbon::parse($workDate)->setTimeFromTimeString($endedAtInput)->format('Y-m-d H:i:s') : null;
        $attendance->remarks = $request->input('remarks');
        $attendance->save();

        // 休憩データを更新
        $attendance->attendanceBreak()->delete();
        $totalBreakMinutes = 0;
        foreach ($request->input('breaks', []) as $break) {
            $breakStart = isset($break['break_start_at']) ? trim((string) $break['break_start_at']) : '';
            $breakEnd = isset($break['break_end_at']) ? trim((string) $break['break_end_at']) : '';
            if ($breakStart === '' || $breakEnd === '') {
                continue;
            }
            try {
                $breakStartAt = \Illuminate\Support\Carbon::parse($workDate)->setTimeFromTimeString($breakStart);
                $breakEndAt = \Illuminate\Support\Carbon::parse($workDate)->setTimeFromTimeString($breakEnd);
            } catch (\Exception $e) {
                continue;
            }
            if ($breakEndAt->lt($breakStartAt)) {
                continue;
            }
            $totalBreakMinutes += $breakEndAt->diffInMinutes($breakStartAt);
            $attendance->attendanceBreak()->create([
                'break_start_at' => $breakStartAt->format('Y-m-d H:i:s'),
                'break_end_at' => $breakEndAt->format('Y-m-d H:i:s'),
            ]);
        }
        // 合計勤務時間を計算（退勤済みの場合のみ）
        $totalWorkMinutes = max(
            0,
            ($attendance->ended_at && $attendance->started_at)
                ? $attendance->ended_at->diffInMinutes($attendance->started_at) - $totalBreakMinutes
                : 0
        );

        $attendance->total_break_minutes = $totalBreakMinutes;
        $attendance->total_work_minutes = $totalWorkMinutes;
        $attendance->save();
        return redirect()->route('attendance.detail', $id)->with('success', '勤怠データを更新しました。');
    }
}

