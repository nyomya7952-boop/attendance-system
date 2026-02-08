<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRequest;
use App\Enums\AttendanceRequestStatus;
use Illuminate\Http\Request;
use App\Models\AttendanceApproval;
use App\Enums\AttendanceApprovalStatus;
use App\Enums\Role;
class RequestController extends Controller
{
    /**
     * 申請一覧画面を表示（管理者）
     */
    public function index()
    {
        $tab = request('tab', 'submitted');

        if ($tab === 'submitted') {
            $attendanceRequests = AttendanceRequest::where('status', AttendanceRequestStatus::SUBMITTED->value)
                ->with('requestedBy')
                ->whereHas('requestedBy', function ($query) {
                    $query->where('role_id', Role::GENERAL_USER->value);
                })
                ->orderBy('requested_by', 'asc')
                ->orderBy('requested_work_date', 'asc')
                ->get();
        } elseif ($tab === 'approved') {
            $attendanceRequests = AttendanceRequest::where('status', AttendanceRequestStatus::APPROVED->value)
                ->with('requestedBy')
                ->whereHas('requestedBy', function ($query) {
                    $query->where('role_id', Role::GENERAL_USER->value);
                })
                ->orderBy('requested_by', 'asc')
                ->orderBy('requested_work_date', 'asc')
                ->get();
        } else {
            return redirect()->route('admin.request.list')->with('error', '不正なタブです。');
        }

        return view('admin.stamp-correction-request-list', [
            'attendanceRequests' => $attendanceRequests,
            'activeTab' => $tab,
        ]);
    }

    /**
     * 修正申請承認画面を表示（管理者）
     */
    public function showApproval($attendance_correct_request_id)
        {
        // リクエストパラメーターに紐づく申請データを取得
        $attendanceRequest = AttendanceRequest::with('attendanceRequestBreaks')->findOrFail($attendance_correct_request_id);
        return view('admin.approve', [
            'attendanceRequest' => $attendanceRequest,
        ]);
    }

    /**
     * 修正申請承認処理（管理者）
     */
    public function approve(Request $request, $attendance_correct_request_id)
    {
        // 承認済みの場合はエラーを表示
        $attendanceApproveCount = AttendanceApproval::where('attendance_request_id', $attendance_correct_request_id)->count();
        if ($attendanceApproveCount > 0) {
            return redirect()->route('admin.request.approve.show', ['attendance_correct_request_id' => $attendance_correct_request_id])
                ->with('error', 'すでに承認済みのため修正できません。');
        }

        // 申請データを取得
        $attendanceRequest = AttendanceRequest::with('attendanceRequestBreaks')->findOrFail($attendance_correct_request_id);

        // 申請データ上「承認済み」に更新
        $attendanceRequest->status = AttendanceRequestStatus::APPROVED->value;
        $attendanceRequest->save();


        // 合計休憩時間を計算
        $totalBreakMinutes = 0;
        $totalBreakMinutes = $attendanceRequest->attendanceRequestBreaks()
                ->whereNotNull('break_end_at')
                ->get()
                ->sum(function ($break) {
                    return $break->break_end_at->diffInMinutes($break->break_start_at);
                });

        // 合計勤務時間を計算（退勤済みの場合のみ）
        $totalWorkMinutes = max(
            0,
            ($attendanceRequest->requested_ended_at && $attendanceRequest->requested_started_at)
                ? $attendanceRequest->requested_ended_at->diffInMinutes($attendanceRequest->requested_started_at) - $totalBreakMinutes
                : 0
        );

        $attendanceApproval = AttendanceApproval::create([
            'attendance_id' => $attendanceRequest->attendance_id,
            'attendance_request_id' => $attendanceRequest->id,
            'approved_by' => auth()->user()->id,
            'approved_at' => now(),
            'status' => AttendanceApprovalStatus::APPROVED->value,
            'final_work_date' => $attendanceRequest->requested_work_date,
            'final_started_at' => $attendanceRequest->requested_started_at,
            'final_ended_at' => $attendanceRequest->requested_ended_at,
            'final_break_minutes' => $totalBreakMinutes,
            'final_work_minutes' => $totalWorkMinutes,
            ]);
        return redirect()->route('admin.request.approve.show', ['attendance_correct_request_id' => $attendanceRequest->id])
            ->with('success', '修正申請を承認しました。');
    }
}

