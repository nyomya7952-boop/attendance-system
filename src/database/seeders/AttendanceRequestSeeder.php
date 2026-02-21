<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Enums\AttendanceRequestStatus;
use App\Enums\Role;

class AttendanceRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendances = Attendance::whereRaw('id % 3 = 1')
            ->whereHas('user', function ($query) {
                $query->where('role_id', Role::GENERAL_USER->value);
            })
            ->get();
        $generalUsers = User::where('role_id', Role::GENERAL_USER->value)->get();
        $adminUsers = User::where('role_id', Role::ADMIN->value)->get();

        if ($attendances->isEmpty() || $generalUsers->isEmpty()) {
            $this->command->warn('勤怠データまたはユーザーデータが存在しません。先にAttendanceSeederとUserSeederを実行してください。');
            return;
        }

        // 各勤怠データに対して0〜2個の申請データを作成
        foreach ($attendances as $attendance) {
            $requestCount = rand(0, 1);

            for ($i = 0; $i < $requestCount; $i++) {
                $requestedStartedAt = \Carbon\Carbon::parse($attendance->started_at);
                $requestedEndedAt = \Carbon\Carbon::parse($attendance->ended_at);

                // 申請者
                $requestedBy = $attendance->user_id;

                $status = $this->getRandomStatus();
                $approver = null;
                if ($status === AttendanceRequestStatus::APPROVED->value) {
                    $approver = $adminUsers->where('id', '!=', $requestedBy)->first()
                        ?? $generalUsers->where('id', '!=', $requestedBy)->first();
                }

                AttendanceRequest::factory()->create([
                    'attendance_id' => $attendance->id,
                    'requested_work_date' => $requestedStartedAt->format('Y-m-d'),
                    'requested_started_at' => $requestedStartedAt,
                    'requested_ended_at' => $requestedEndedAt,
                    'remarks' => $attendance->remarks,
                    'requested_by' => $requestedBy,
                    'approver_id' => $approver?->id,
                    'status' => $status,
                ]);


            }
        }
    }

    /**
     * ランダムな備考を取得
     */
    private function getRandomRemarks(): string
    {
        $remarks = [
            '出勤時刻の修正申請です。',
            '退勤時刻の修正申請です。',
            '打刻忘れのため修正申請します。',
            'システムエラーのため修正申請します。',
            '勤務時間の訂正をお願いします。',
        ];
        return $remarks[array_rand($remarks)];
    }

    /**
     * ランダムなステータスを取得
     */
    private function getRandomStatus(): string
    {
        $statuses = [
            AttendanceRequestStatus::SUBMITTED->value,
            AttendanceRequestStatus::APPROVED->value,
        ];
        return $statuses[array_rand($statuses)];
    }
}

