<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Database\Seeder;

class AttendanceBreakSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendances = Attendance::all();

        if ($attendances->isEmpty()) {
            $this->command->warn('勤怠データが存在しません。先にAttendanceSeederを実行してください。');
            return;
        }

        // 各勤怠データに対して1〜3個の休憩データを作成
        foreach ($attendances as $attendance) {
            $breakCount = rand(1, 3);

            for ($i = 0; $i < $breakCount; $i++) {
                // 勤怠の開始時刻と終了時刻の間で休憩時間を設定
                $startedAt = \Carbon\Carbon::parse($attendance->started_at);
                $endedAt = \Carbon\Carbon::parse($attendance->ended_at);

                // 休憩開始時刻（勤怠開始から2時間後〜終了2時間前の間）
                $breakStartAt = $startedAt->copy()->addHours(2 + ($i * 2));

                // 休憩終了時刻（休憩開始から15〜60分後）
                $breakEndAt = $breakStartAt->copy()->addMinutes(rand(15, 60));

                // 勤怠終了時刻を超えないように調整
                if ($breakEndAt->gt($endedAt->copy()->subHours(1))) {
                    $breakEndAt = $endedAt->copy()->subHours(1);
                    $breakStartAt = $breakEndAt->copy()->subMinutes(rand(15, 60));
                }

                AttendanceBreak::factory()->create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $breakStartAt,
                    'break_end_at' => $breakEndAt,
                ]);
            }
        }
    }
}

