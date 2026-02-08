<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Enums\AttendanceRequestStatus;

class AttendanceRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendances = Attendance::whereRaw('id % 3 = 1')->get();
        $users = User::all();

        if ($attendances->isEmpty() || $users->isEmpty()) {
            $this->command->warn('勤怠データまたはユーザーデータが存在しません。先にAttendanceSeederとUserSeederを実行してください。');
            return;
        }

        // 各勤怠データに対して0〜2個の申請データを作成
        foreach ($attendances as $attendance) {
            $requestCount = rand(0, 2);

            for ($i = 0; $i < $requestCount; $i++) {
                $requestedStartedAt = \Carbon\Carbon::parse($attendance->started_at)
                    ->addMinutes(rand(-30, 30));
                $requestedEndedAt = \Carbon\Carbon::parse($attendance->ended_at)
                    ->addMinutes(rand(-30, 30));

                // 申請者
                $requestedBy = $users->random();

                // 承認者（申請者以外のユーザー、管理者ロールを持つユーザーを優先）
                $approver = $users->where('id', '!=', $requestedBy->id)
                    ->where('role_id', 2) // 管理者ロール
                    ->first() ?? $users->where('id', '!=', $requestedBy->id)->first();

                AttendanceRequest::factory()->create([
                    'attendance_id' => $attendance->id,
                    'requested_work_date' => $requestedStartedAt->format('Y-m-d'),
                    'requested_started_at' => $requestedStartedAt,
                    'requested_ended_at' => $requestedEndedAt,
                    'remarks' => $this->getRandomRemarks(),
                    'requested_by' => $requestedBy->id,
                    'approver_id' => $approver?->id,
                    'status' => $this->getRandomStatus(),
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

