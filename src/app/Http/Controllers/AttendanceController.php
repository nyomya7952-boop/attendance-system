<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRequestBreak;
use App\Enums\AttendanceRequestStatus;
use App\Http\Requests\AttendanceUpdateRequest;

class AttendanceController extends Controller
{
    /**
     * 勤怠一覧画面を表示（一般ユーザー）
     */
    public function index(Request $request)
    {
        $rawMonth = (string) $request->get('month', now()->format('Y-m'));
        $currentMonth = str_replace('/', '-', $rawMonth);
        $date = \Carbon\Carbon::parse($currentMonth . '-01');
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        // 前月・翌月の取得
        $prevMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 勤怠データの取得
        $attendances = Attendance::where('user_id', auth()->id())
            ->whereBetween('work_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('work_date', 'asc')
            ->get()
            ->keyBy(function ($attendance) {
                $workDate = is_string($attendance->work_date)
                    ? \Carbon\Carbon::parse($attendance->work_date)
                    : $attendance->work_date;
                return $workDate->toDateString();
            });

        $attendanceRows = collect();
        for ($day = $monthStart->copy(); $day->lte($monthEnd); $day->addDay()) {
            $attendance = $attendances->get($day->toDateString());
            $dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][$day->dayOfWeek];
            if ($attendance) {
                $attendance->formatted_date = $day->format('m/d') . '(' . $dayOfWeek . ')';
                $attendanceRows->push($attendance);
                continue;
            }

            $attendanceRows->push((object) [
                'formatted_date' => $day->format('m/d') . '(' . $dayOfWeek . ')',
                'work_date' => $day->toDateString(),
                'started_at' => null,
                'ended_at' => null,
                'total_break_minutes' => null,
                'total_work_minutes' => null,
                'id' => null,
            ]);
        }

        return view('attendance-list', [
            'currentMonth' => $date->format('Y/m'),
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'attendanceRows' => $attendanceRows,
        ]);
    }

    /**
     * 勤怠詳細画面を表示（一般ユーザー）
     */
    public function show($id)
    {
        // リクエストパラメーターに紐づく勤怠データを取得
        $attendance = Attendance::with([
            'attendanceBreak' => function ($query) {
                $query->orderBy('break_start_at', 'asc');
            },
        ])->findOrFail($id);
        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)->first();
        return view('attendance-show', [
            'attendance' => $attendance,
            'attendanceBreaks' => $attendance->attendanceBreak,
            'attendanceRequest' => $attendanceRequest,
        ]);
    }

    /**
     * 勤怠詳細更新処理（一般ユーザー）
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

