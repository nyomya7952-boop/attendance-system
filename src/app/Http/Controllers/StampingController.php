<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use App\Services\AttendanceService;

class StampingController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }
    /**
     * 出勤登録画面を表示（一般ユーザー）
     */
    public function index()
    {
        // 日付を取得
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $weekdays[now()->format('w')];
        $date = now()->format('Y年n月j日') . '(' . $weekday . ')';

        $attendance = Attendance::where('user_id', auth()->user()->id)
            ->where('work_date', now()->format('Y-m-d'))
            ->with(['attendanceBreak' => function($query) {
                $query->orderBy('break_start_at', 'desc')->limit(1);
            }])
            ->first();

        $attendanceBreak = null;
        $attendanceStatus = AttendanceStatus::OUTSIDE_WORK;

        if ($attendance) {
            // work_dateが本日であり、かつended_atがNullではない(退勤済)
            if ($attendance->ended_at) {
                $attendanceStatus = AttendanceStatus::CLOCKED_OUT;
            } else {
                $attendanceBreak = $attendance->attendanceBreak->first();
                if ($attendanceBreak && !$attendanceBreak->break_end_at) {
                    // attendanceBreakが存在し、かつbreak_end_atがNullである(休憩中)
                    $attendanceStatus = AttendanceStatus::ON_BREAK;
                } else {
                    $attendanceStatus = AttendanceStatus::CLOCKED_IN;
                }
            }
        }

        return view('attendance', ['date' => $date, 'attendanceStatus' => $attendanceStatus, 'attendance' => $attendance, 'attendanceBreak' => $attendanceBreak]);
    }

    /**
     * 出勤登録処理（一般ユーザー）
     */
    public function store(Request $request)
    {
        $action = $request->input('action');

        switch ($action) {
            case 'clock_in':
                return $this->attendanceService->clockIn($request); // 出勤処理

            case 'clock_out':
                return $this->attendanceService->clockOut($request); // 退勤処理

            case 'break_start':
                return $this->attendanceService->breakStart($request); // 休憩開始処理

            case 'break_end':
                return $this->attendanceService->breakEnd($request); // 休憩終了処理

            default:
                return redirect()->route('attendance.index')
                    ->with('error', '不正な操作です。');
        }
    }
}