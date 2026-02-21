<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Role as RoleModel;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Enums\AttendanceStatus;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    private function seedRoles(): void
    {
        RoleModel::query()->forceCreate([
            'id' => RoleEnum::GENERAL_USER->value,
            'name' => RoleEnum::GENERAL_USER->label(),
        ]);
    }

    /**
     * 自分が行った勤怠情報が全て表示されている
     * 自分の勤怠情報が全て表示されていることを確認
     */
    public function testAttendanceList()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        $currentMonth = now()->format('Y-m');
        $date = Carbon::parse($currentMonth . '-01');
        $monthStart = $date->copy()->startOfMonth();
        $monthEnd = $date->copy()->endOfMonth();

        // 1ヶ月分の勤怠データを作成
        for ($day = $monthStart->copy(); $day->lte($monthEnd); $day->addDay()) {
            $workDate = $day->format('Y-m-d');

            // 既にその日の勤怠データが存在する場合はスキップ
            if (Attendance::where('user_id', $user->id)
                ->where('work_date', $workDate)
                ->exists()) {
                continue;
            }

            $startedAt = $day->copy()->setTime(9, 0, 0)->addMinutes(rand(-30, 30));
            $endedAt = $startedAt->copy()->addHours(8)->addMinutes(rand(-30, 30));

            Attendance::factory()->create([
                'user_id' => $user->id,
                'work_date' => $workDate,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'status' => AttendanceStatus::CLOCKED_OUT,
            ]);
        }

        $attendances = Attendance::where('user_id', $user->id)->get();
        // 各勤怠データに対して1〜3個の休憩データを作成
        foreach ($attendances as $attendance) {
            $breakCount = rand(1, 3);
            $breakTotalMinutes = 0;

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

                $breakTotalMinutes += $breakEndAt->diffInMinutes($breakStartAt);
                AttendanceBreak::factory()->create([
                    'attendance_id' => $attendance->id,
                    'break_start_at' => $breakStartAt,
                    'break_end_at' => $breakEndAt,
                ]);
            }
            $attendance->total_break_minutes = $breakTotalMinutes;
            $attendance->total_work_minutes = $endedAt->diffInMinutes($startedAt) - $breakTotalMinutes;
            $attendance->save();
        }

        // ログイン
        $this->actingAs($user);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 自分の勤怠情報が全て表示されていることを確認
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        foreach ($attendances as $attendance) {
            $workDate = Carbon::parse($attendance->work_date);
            $response->assertSee($workDate->format('m/d') . '(' . $weekdays[$workDate->dayOfWeek] . ')');
            $response->assertSee(Carbon::parse($attendance->started_at)->format('H:i'));
            $response->assertSee(Carbon::parse($attendance->ended_at)->format('H:i'));

            $breakMinutes = (int) $attendance->total_break_minutes;
            $breakHours = intdiv($breakMinutes, 60);
            $breakRemainder = $breakMinutes % 60;
            $response->assertSee($breakHours . ':' . str_pad((string) $breakRemainder, 2, '0', STR_PAD_LEFT));

            $workMinutes = (int) $attendance->total_work_minutes;
            $workHours = intdiv($workMinutes, 60);
            $workRemainder = $workMinutes % 60;
            $response->assertSee($workHours . ':' . str_pad((string) $workRemainder, 2, '0', STR_PAD_LEFT));
        }

        Carbon::setTestNow();
    }

    /**
     * 勤怠一覧画面に遷移した際に現在の月が表示される
     * 現在の月が表示されていることを確認
     */
    public function testAttendanceListCurrentMonth()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 現在の月が表示されていることを確認
        $response->assertSee(now()->format('Y/m'));

        Carbon::setTestNow();
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     * 前月の情報が表示されていることを確認
     */
    public function testAttendanceListPreviousMonthButton()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 「前月」を押下
        $response = $this->get(route('attendance.list', [
            'month' => now()->subMonth()->format('Y-m'),
        ]));
        $response->assertStatus(200);

        // 前月の情報が表示されていることを確認
        $response->assertSee(now()->subMonth()->format('Y/m'));

        Carbon::setTestNow();
    }

    /**
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     * 翌月の情報が表示されていることを確認
     */
    public function testAttendanceListNextMonthButton()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 「翌月」を押下
        $response = $this->get(route('attendance.list', [
            'month' => now()->addMonth()->format('Y-m'),
        ]));
        $response->assertStatus(200);

        // 翌月の情報が表示されていることを確認
        $response->assertSee(now()->addMonth()->format('Y/m'));

        Carbon::setTestNow();
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     * その日の勤怠詳細画面に遷移することを確認
     */
    public function testAttendanceListDetailButton()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
            'status' => AttendanceStatus::CLOCKED_OUT,
            'ended_at' => now()->addHour(8),
            'total_break_minutes' => 60,
            'total_work_minutes' => 420,
            'remarks' => 'テスト',
        ]);

        $attendanceBreak = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now()->addHour(3),
            'break_end_at' => now()->addHour(3)->addMinute(60),
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);
        $response->assertSee(route('attendance.detail', $attendance->id));

        // 「詳細」を押下
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // その日の勤怠詳細画面に遷移することを確認
        $response->assertSee(Carbon::parse($attendance->work_date)->format('n月j日'));
        $response->assertSee(Carbon::parse($attendance->started_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendance->ended_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceBreak->break_start_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceBreak->break_end_at)->format('H:i'));
        $response->assertSee($attendance->remarks);

        Carbon::setTestNow();
    }
}
