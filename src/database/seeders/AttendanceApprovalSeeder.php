<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceApproval;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Enums\AttendanceApprovalStatus;
use App\Enums\AttendanceRequestStatus;
class AttendanceApprovalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendanceRequests = AttendanceRequest::where('status', AttendanceRequestStatus::APPROVED->value)->get();
        $users = User::all();

        if ($attendanceRequests->isEmpty() || $users->isEmpty()) {
            $this->command->warn('対象月（前々月）の申請データが存在しません。先にAttendanceRequestSeederを実行してください。');
            return;
        }

        // 各申請データに対して承認データを作成
        foreach ($attendanceRequests as $request) {
            $attendance = Attendance::find($request->attendance_id);

            if (!$attendance) {
                continue;
            }

            // 承認者（申請のapprover_idまたは管理者ロールを持つユーザー）
            $approver = $users->where('id', $request->approver_id)->first()
                ?? $users->where('role_id', 2)->first() // 管理者ロール
                ?? $users->first();

            $finalStartedAt = \Carbon\Carbon::parse($request->requested_started_at);
            $finalEndedAt = \Carbon\Carbon::parse($request->requested_ended_at);
            $finalWorkMinutes = $finalStartedAt->diffInMinutes($finalEndedAt);
            $finalBreakMinutes = rand(0, 60);

            AttendanceApproval::factory()->create([
                'attendance_id' => $attendance->id,
                'attendance_request_id' => $request->id,
                'approved_by' => $approver->id,
                'approved_at' => now()->subDays(rand(0, 7)),
                'status' => AttendanceApprovalStatus::APPROVED->value,
                'final_work_date' => $finalStartedAt->format('Y-m-d'),
                'final_started_at' => $finalStartedAt,
                'final_ended_at' => $finalEndedAt,
                'final_break_minutes' => $finalBreakMinutes,
                'final_work_minutes' => $finalWorkMinutes - $finalBreakMinutes,
            ]);
        }
    }
}

