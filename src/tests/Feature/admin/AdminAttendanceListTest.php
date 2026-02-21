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

class AdminAttendanceListTest extends TestCase
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
        RoleModel::query()->forceCreate([
            'id' => RoleEnum::ADMIN->value,
            'name' => RoleEnum::ADMIN->label(),
        ]);
    }

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     * その日の全ユーザーの勤怠情報が正確な値で表示されていることを確認
     */
    public function testAttendanceList()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);
        $user2 = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        $user3 = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);

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
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now()->addMinute(10),
            'status' => AttendanceStatus::CLOCKED_OUT,
            'ended_at' => now()->addHour(5)->addMinute(10),
            'total_break_minutes' => 10,
            'total_work_minutes' => 290,
            'remarks' => 'テスト2',
        ]);
        $attendanceBreak2 = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_start_at' => now()->addHour(3),
            'break_end_at' => now()->addHour(3)->addMinute(10),
        ]);
        $attendance3 = Attendance::factory()->create([
            'user_id' => $user3->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now()->addHour(),
            'status' => AttendanceStatus::CLOCKED_OUT,
            'ended_at' => now()->addHour(9),
            'total_break_minutes' => 60,
            'total_work_minutes' => 420,
            'remarks' => 'テスト3',
        ]);
        $attendanceBreak3 = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance3->id,
            'break_start_at' => now()->addHour(3),
            'break_end_at' => now()->addHour(3)->addMinute(60),
        ]);
        // ログイン
        $this->actingAs($admin);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('admin.attendance.list'));
        $response->assertStatus(200);

        // 全ユーザーの勤怠情報が正確に確認できることを確認
        // ユーザ1
        $response->assertSee($user->name);
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

        // ユーザ2
        $response->assertSee($user2->name);
        $response->assertSee(Carbon::parse($attendance2->started_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendance2->ended_at)->format('H:i'));

        $breakMinutes = (int) $attendance2->total_break_minutes;
        $breakHours = intdiv($breakMinutes, 60);
        $breakRemainder = $breakMinutes % 60;
        $response->assertSee($breakHours . ':' . str_pad((string) $breakRemainder, 2, '0', STR_PAD_LEFT));

        $workMinutes = (int) $attendance2->total_work_minutes;
        $workHours = intdiv($workMinutes, 60);
        $workRemainder = $workMinutes % 60;
        $response->assertSee($workHours . ':' . str_pad((string) $workRemainder, 2, '0', STR_PAD_LEFT));

        // ユーザ3
        $response->assertSee($user3->name);
        $response->assertSee(Carbon::parse($attendance3->started_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendance3->ended_at)->format('H:i'));

        $breakMinutes = (int) $attendance3->total_break_minutes;
        $breakHours = intdiv($breakMinutes, 60);
        $breakRemainder = $breakMinutes % 60;
        $response->assertSee($breakHours . ':' . str_pad((string) $breakRemainder, 2, '0', STR_PAD_LEFT));

        $workMinutes = (int) $attendance3->total_work_minutes;
        $workHours = intdiv($workMinutes, 60);
        $workRemainder = $workMinutes % 60;
        $response->assertSee($workHours . ':' . str_pad((string) $workRemainder, 2, '0', STR_PAD_LEFT));

        Carbon::setTestNow();
    }

    /**
     * 遷移した際に現在の日付が表示される
     * 勤怠一覧画面にその日の日付が表示されていることを確認
     */
    public function testAttendanceListCurrentDate()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        // ログイン
        $this->actingAs($admin);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('admin.attendance.list'));
        $response->assertStatus(200);

        // 現在の日付が表示されていることを確認
        $response->assertSee(now()->format('Y/m/d'));

        Carbon::setTestNow();
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     * 前日の日付の勤怠情報が表示されることを確認
     */
    public function testAttendanceListPreviousDate()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        // ログイン
        $this->actingAs($admin);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('admin.attendance.list'));
        $response->assertStatus(200);

        // 「前日」を押下
        $response = $this->get(route('admin.attendance.list', [
            'date' => now()->subDay()->format('Y-m-d'),
        ]));
        $response->assertStatus(200);

        // 前日の日付の勤怠情報が表示されていることを確認
        $response->assertSee(now()->subDay()->format('Y/m/d'));

        Carbon::setTestNow();
    }

    /**
     * 「翌日」を押下した時に翌日の勤怠情報が表示される
     * 翌日の日付の勤怠情報が表示されることを確認
     */
    public function testAttendanceListNextDate()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        // ログイン
        $this->actingAs($admin);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('admin.attendance.list'));
        $response->assertStatus(200);

        // 「翌日」を押下
        $response = $this->get(route('admin.attendance.list', [
            'date' => now()->addDay()->format('Y-m-d'),
        ]));
        $response->assertStatus(200);

        // 翌日の日付の勤怠情報が表示されていることを確認
        $response->assertSee(now()->addDay()->format('Y/m/d'));

        Carbon::setTestNow();
    }
}
