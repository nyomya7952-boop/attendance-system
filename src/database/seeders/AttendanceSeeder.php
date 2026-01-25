<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('ユーザーが存在しません。先にUserSeederを実行してください。');
            return;
        }

        // 各ユーザーに対して過去30日分の勤怠データを作成
        foreach ($users as $user) {
            for ($i = 0; $i < 30; $i++) {
                $workDate = now()->subDays($i)->format('Y-m-d');

                // 既にその日の勤怠データが存在する場合はスキップ
                if (Attendance::where('user_id', $user->id)
                    ->where('work_date', $workDate)
                    ->exists()) {
                    continue;
                }

                $startedAt = now()->subDays($i)->setTime(9, 0, 0);
                $endedAt = (clone $startedAt)->modify('+8 hours');

                Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $workDate,
                    'started_at' => $startedAt,
                    'ended_at' => $endedAt,
                    'status' => $this->getRandomStatus(),
                    'total_break_minutes' => rand(0, 60),
                    'total_work_minutes' => rand(240, 480),
                ]);
            }
        }
    }

    /**
     * ランダムなステータスを取得
     */
    private function getRandomStatus(): string
    {
        $statuses = ['draft', 'submitted', 'approved', 'rejected'];
        return $statuses[array_rand($statuses)];
    }
}

