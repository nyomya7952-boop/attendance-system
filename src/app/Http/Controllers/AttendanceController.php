<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    /**
     * 勤怠一覧画面を表示（一般ユーザー）
     */
    public function index(Request $request)
    {
        $currentMonth = $request->get('month', now()->format('Y-m'));
        $date = \Carbon\Carbon::parse($currentMonth . '-01');
        $attendances = Attendance::where('user_id', auth()->id())
            ->whereYear('work_date', $date->year)
            ->whereMonth('work_date', $date->month)
            ->orderBy('work_date', 'asc')
            ->get()
            ->map(function ($attendance) {
                $workDate = is_string($attendance->work_date)
                    ? \Carbon\Carbon::parse($attendance->work_date)
                    : $attendance->work_date;
                $dayOfWeek = ['日', '月', '火', '水', '木', '金', '土'][$workDate->dayOfWeek];
                $attendance->formatted_date = $workDate->format('m/d') . '(' . $dayOfWeek . ')';
                return $attendance;
            });

        return view('attendance-list', [
            'currentMonth' => $date->format('Y/m'),
            'attendances' => $attendances,
        ]);
    }

    /**
     * 勤怠詳細画面を表示（一般ユーザー）
     */
    public function show($id)
    {
        // リクエストパラメーターに紐づく勤怠データを取得
        $attendance = Attendance::with('attendanceBreaks')->find($id);
        return view('attendance-show', [
            'attendance' => $attendance,
            'attendanceBreaks' => $attendance->attendanceBreaks ?? collect(),
        ]);
    }

    /**
     * 勤怠詳細更新処理（一般ユーザー）
     */
    public function update(Request $request, $id)
    {
        // TODO: 勤怠詳細更新処理を実装
    }
}

