<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Enums\AttendanceRequestStatus;

class RequestController extends Controller
{
    /**
     * 申請一覧画面を表示（一般ユーザー）
     */
    public function index(Request $request)
    {
        // 前月分の勤怠データがすべて申請済みであることを確認する
        $attendances = Attendance::leftJoin('attendance_requests', 'attendance_requests.attendance_id', '=', 'attendances.id')
            ->where('attendances.user_id', auth()->id())
            ->whereDate('attendances.work_date', '<=', now()->subMonth()->endOfMonth())
            ->select('attendances.*', 'attendance_requests.id as attendance_request_id')
            ->get();
        if ($attendances->isEmpty()) {
            return redirect()->route('request.list')->with('error', '前月分の勤怠データが存在しません。');
        }
        if ($attendances->contains(fn ($attendance) => is_null($attendance->attendance_request_id))) {
            // 前月分の勤怠データを申請する
            foreach ($attendances as $attendance) {
                AttendanceRequest::create([
                    'attendance_id' => $attendance->id,
                    'requested_work_date' => $attendance->work_date,
                    'requested_started_at' => $attendance->started_at,
                    'requested_ended_at' => $attendance->ended_at,
                    'remarks' => $attendance->remarks,
                    'requested_by' => auth()->id(),
                    'status' => AttendanceRequestStatus::SUBMITTED->value,
                ]);
                // 紐づく休憩データも申請する
                foreach ($attendance->attendanceBreak() as $break) {
                    AttendanceRequestBreak::create([
                        'attendance_request_id' => $attendanceRequest->id,
                        'break_start_at' => $break->break_start_at,
                        'break_end_at' => $break->break_end_at,
                    ]);
                }
            }

            return redirect()->route('request.list')->with('success', '前月分の勤怠データを申請しました。');
        }

        $tab = $request->get('tab', 'submitted'); // デフォルトは承認待ち

        if ($tab === 'submitted') {
            $attendanceRequests = AttendanceRequest::where('requested_by', auth()->id())
                ->where('status', AttendanceRequestStatus::SUBMITTED->value)
                ->get();
        } elseif ($tab === 'approved') {
            $attendanceRequests = AttendanceRequest::where('requested_by', auth()->id())
                ->where('status', AttendanceRequestStatus::APPROVED->value)
                ->get();
        } else {
            return redirect()->route('request.list')->with('error', '不正なタブです。');
        }
        return view('stamp-correction-request-list', [
            'attendanceRequests' => $attendanceRequests,
            'activeTab' => $tab,
        ]);
    }
}

