<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceDetailStoreRequest;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceDetailController extends Controller
{
    /**
     * 勤怠詳細画面を表示（未登録日の場合は空フォーム）【管理者】
     */
    public function showByDate(Request $request)
    {
        $dateParam = $request->get('date');
        if (!$dateParam) {
            return redirect()->route('admin.attendance.list');
        }

        try {
            $workDate = Carbon::parse($dateParam)->toDateString();
        } catch (\Exception $e) {
            return redirect()->route('admin.attendance.list');
        }

        if (auth()->user()->role_id !== Role::ADMIN->value) {
            return redirect()->route('admin.attendance.list');
        }

        $targetUserId = (int) $request->get('user_id', auth()->id());

        $attendance = Attendance::where('user_id', $targetUserId)
            ->where('work_date', $workDate)
            ->first();

        if ($attendance) {
            return redirect()->route('admin.attendance.show', $attendance->id);
        }

        $attendance = new Attendance([
            'user_id' => $targetUserId,
            'work_date' => $workDate,
            'started_at' => null,
            'ended_at' => null,
            'remarks' => '',
        ]);
        $attendance->setRelation('user', User::find($targetUserId) ?? auth()->user());

        return view('admin.attendance-show', [
            'attendance' => $attendance,
            'attendanceBreaks' => collect(),
            'attendanceRequest' => null,
            'isNew' => true,
        ]);
    }

    /**
     * 勤怠詳細登録処理（未登録日）【管理者】
     */
    public function storeByDate(AttendanceDetailStoreRequest $request)
    {
        $dateParam = $request->get('date');
        if (!$dateParam) {
            return redirect()->route('admin.attendance.list');
        }

        try {
            $workDate = Carbon::parse($dateParam)->toDateString();
        } catch (\Exception $e) {
            return redirect()->route('admin.attendance.list');
        }

        if (auth()->user()->role_id !== Role::ADMIN->value) {
            return redirect()->route('admin.attendance.list');
        }

        $targetUserId = (int) $request->get('user_id', auth()->id());

        $exists = Attendance::where('user_id', $targetUserId)
            ->where('work_date', $workDate)
            ->first();
        if ($exists) {
            return redirect()->route('admin.attendance.show', $exists->id);
        }

        $startedAtInput = $request->input('started_at');
        $endedAtInput = $request->input('ended_at');
        $startedAt = Carbon::parse($workDate)->setTimeFromTimeString($startedAtInput);
        $endedAt = $endedAtInput ? Carbon::parse($workDate)->setTimeFromTimeString($endedAtInput) : null;

        $attendance = Attendance::create([
            'user_id' => $targetUserId,
            'work_date' => $workDate,
            'started_at' => $startedAt->format('Y-m-d H:i:s'),
            'ended_at' => $endedAt ? $endedAt->format('Y-m-d H:i:s') : null,
            'status' => $endedAt ? AttendanceStatus::CLOCKED_OUT->value : AttendanceStatus::CLOCKED_IN->value,
            'remarks' => $request->input('remarks'),
        ]);

        $totalBreakMinutes = 0;
        foreach ($request->input('breaks', []) as $break) {
            $breakStart = isset($break['break_start_at']) ? trim((string) $break['break_start_at']) : '';
            $breakEnd = isset($break['break_end_at']) ? trim((string) $break['break_end_at']) : '';
            if ($breakStart === '' || $breakEnd === '') {
                continue;
            }
            try {
                $breakStartAt = Carbon::parse($workDate)->setTimeFromTimeString($breakStart);
                $breakEndAt = Carbon::parse($workDate)->setTimeFromTimeString($breakEnd);
            } catch (\Exception $e) {
                continue;
            }
            if ($breakEndAt->lt($breakStartAt)) {
                continue;
            }
            $totalBreakMinutes += $breakEndAt->diffInMinutes($breakStartAt);
            AttendanceBreak::create([
                'attendance_id' => $attendance->id,
                'break_start_at' => $breakStartAt->format('Y-m-d H:i:s'),
                'break_end_at' => $breakEndAt->format('Y-m-d H:i:s'),
            ]);
        }

        $totalWorkMinutes = $endedAt
            ? max(0, $endedAt->diffInMinutes($startedAt) - $totalBreakMinutes)
            : 0;

        $attendance->total_break_minutes = $totalBreakMinutes;
        $attendance->total_work_minutes = $totalWorkMinutes;
        $attendance->save();

        return redirect()->route('admin.attendance.show', $attendance->id)->with('success', '勤怠データを登録しました。');
    }
}
