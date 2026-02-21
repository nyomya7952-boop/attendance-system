<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use App\Enums\AttendanceStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('attendances')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $users = User::whereIn('id', [1, 4, 5, 7, 10])->get();

        if ($users->isEmpty()) {
            $this->command->warn('ユーザーが存在しません。先にUserSeederを実行してください。');
            return;
        }

        // 各ユーザーに対して過去20日分の勤怠データを作成
        foreach ($users as $user) {
            for ($i = 0; $i < 20; $i++) {
                $workDate = now()->subDays($i)->format('Y-m-d');

                // 既にその日の勤怠データが存在する場合はスキップ
                if (Attendance::where('user_id', $user->id)
                    ->where('work_date', $workDate)
                    ->exists()) {
                    continue;
                }

                $startedAt = now()->subDays($i)->setTime(9, 0, 0)->addMinutes(rand(-30, 30));
                $endedAt = (clone $startedAt)->modify('+8 hours')->addMinutes(rand(-30, 30));
                $totalBreakMinutes = rand(0, 90);

                Attendance::factory()->create([
                    'user_id' => $user->id,
                    'work_date' => $workDate,
                    'started_at' => $startedAt,
                    'ended_at' => $endedAt,
                    'status' => AttendanceStatus::CLOCKED_OUT,
                    'total_break_minutes' => $totalBreakMinutes,
                    'total_work_minutes' => $endedAt->diffInMinutes($startedAt) - $totalBreakMinutes,
                ]);
            }
        }

        // 前月・前々月分の勤怠データも作成（各月の全日）
        $monthsToSeed = [
            now()->subMonthNoOverflow(),
            now()->subMonthsNoOverflow(2),
        ];

        foreach ($monthsToSeed as $targetMonth) {
            foreach ($users as $user) {
                $daysInMonth = $targetMonth->daysInMonth;

                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $workDate = $targetMonth->copy()->day($day)->format('Y-m-d');

                    if (Attendance::where('user_id', $user->id)
                        ->where('work_date', $workDate)
                        ->exists()) {
                        continue;
                    }

                    $startedAt = $targetMonth->copy()->day($day)->setTime(9, 0, 0);
                    $endedAt = (clone $startedAt)->modify('+8 hours');
                    $totalBreakMinutes = rand(0, 90);

                    Attendance::factory()->create([
                        'user_id' => $user->id,
                        'work_date' => $workDate,
                        'started_at' => $startedAt,
                        'ended_at' => $endedAt,
                        'status' => AttendanceStatus::CLOCKED_OUT,
                        'total_break_minutes' => null,
                        'total_work_minutes' => null,
                        'remarks' => $this->getRandomRemarks(),
                    ]);
                }
            }
        }
    }

    /**
     * ランダムな備考を取得
     */
    private function getRandomRemarks(): string
    {
        $remarks = [
            '勤怠データを作成しました。',
            '勤怠データを修正しました。',
            '勤怠データを削除しました。',
        ];
        return $remarks[array_rand($remarks)];
    }
}

