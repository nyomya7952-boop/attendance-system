<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Enums\Role;
use Carbon\Carbon;

class StaffController extends Controller
{
    /**
     * スタッフ一覧画面を表示（管理者）
     */
    public function index()
    {
        // 一般ユーザーを取得
        $staffs = User::where('role_id', Role::GENERAL_USER->value)->get();
        return view('admin.staff-list', compact('staffs'));
    }

    /**
     * スタッフ別勤怠一覧画面を表示（管理者）
     */
    public function showAttendanceStaffList(Request $request, $id)
    {
        $rawMonth = (string) $request->get('month', now()->format('Y-m'));
        $currentMonth = str_replace('/', '-', $rawMonth);
        $date = Carbon::parse($currentMonth . '-01');
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        // 前月・翌月の取得
        $prevMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $staff = User::findOrFail($id);
        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('work_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('work_date', 'asc')
            ->get()
            ->keyBy(function ($attendance) {
                $workDate = is_string($attendance->work_date)
                    ? Carbon::parse($attendance->work_date)
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
                'user_id' => $staff->id,
                'started_at' => null,
                'ended_at' => null,
                'total_break_minutes' => null,
                'total_work_minutes' => null,
                'id' => null,
            ]);
        }

        return view('admin.attendance-staff-list', [
            'staff' => $staff,
            'currentMonth' => $date->format('Y/m'),
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'attendanceRows' => $attendanceRows,
        ]);
    }

    /**
     * スタッフ別勤怠CSV出力（管理者）
     */
    public function exportAttendanceStaffCsv(Request $request, $id)
    {
        $currentMonth = $request->get('month', now()->format('Y-m'));
        $date = Carbon::parse($currentMonth . '-01');

        $staff = User::findOrFail($id);
        $attendances = Attendance::where('user_id', $id)
            ->whereYear('work_date', $date->year)
            ->whereMonth('work_date', $date->month)
            ->orderBy('work_date', 'asc')
            ->get();

        $filename = 'staff_attendance_' . $staff->name . '_' . $date->format('Y_m') . '.csv';

        return response()->streamDownload(function () use ($attendances, $staff) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['氏名', '日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($attendances as $attendance) {
                $workDate = is_string($attendance->work_date)
                    ? Carbon::parse($attendance->work_date)
                    : $attendance->work_date;

                $startedAt = $attendance->started_at
                    ? Carbon::parse($attendance->started_at)->format('H:i')
                    : '-';
                $endedAt = $attendance->ended_at
                    ? Carbon::parse($attendance->ended_at)->format('H:i')
                    : '-';

                $breakTime = $attendance->total_break_minutes
                    ? floor($attendance->total_break_minutes / 60) . ':' . str_pad($attendance->total_break_minutes % 60, 2, '0', STR_PAD_LEFT)
                    : '-';
                $workTime = $attendance->total_work_minutes
                    ? floor($attendance->total_work_minutes / 60) . ':' . str_pad($attendance->total_work_minutes % 60, 2, '0', STR_PAD_LEFT)
                    : '-';

                fputcsv($handle, [
                    $staff->name,
                    $workDate->format('Y-m-d'),
                    $startedAt,
                    $endedAt,
                    $breakTime,
                    $workTime,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}