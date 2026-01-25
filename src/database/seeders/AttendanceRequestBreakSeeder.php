<?php

namespace Database\Seeders;

use App\Models\AttendanceRequest;
use App\Models\AttendanceRequestsBreak;
use Illuminate\Database\Seeder;

class AttendanceRequestBreakSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $attendanceRequests = AttendanceRequest::all();

        if ($attendanceRequests->isEmpty()) {
            $this->command->warn('勤怠申請データが存在しません。先にAttendanceRequestSeederを実行してください。');
            return;
        }

        // 各申請データに対して1〜3個の休憩データを作成
        foreach ($attendanceRequests as $request) {
            $breakCount = rand(1, 3);

            for ($i = 0; $i < $breakCount; $i++) {
                // 申請の開始時刻と終了時刻の間で休憩時間を設定
                $requestedStartedAt = \Carbon\Carbon::parse($request->requested_started_at);
                $requestedEndedAt = \Carbon\Carbon::parse($request->requested_ended_at);

                // 休憩開始時刻（申請開始から2時間後〜終了2時間前の間）
                $breakStartAt = $requestedStartedAt->copy()->addHours(2 + ($i * 2));

                // 休憩終了時刻（休憩開始から15〜60分後）
                $breakEndAt = $breakStartAt->copy()->addMinutes(rand(15, 60));

                // 申請終了時刻を超えないように調整
                if ($breakEndAt->gt($requestedEndedAt->copy()->subHours(1))) {
                    $breakEndAt = $requestedEndedAt->copy()->subHours(1);
                    $breakStartAt = $breakEndAt->copy()->subMinutes(rand(15, 60));
                }

                AttendanceRequestsBreak::factory()->create([
                    'attendance_request_id' => $request->id,
                    'break_start_at' => $breakStartAt,
                    'break_end_at' => $breakEndAt,
                ]);
            }
        }
    }
}

