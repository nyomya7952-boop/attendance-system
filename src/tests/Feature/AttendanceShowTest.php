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

class AttendanceShowTest extends TestCase
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
     * 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
     * 名前がログインユーザーの氏名になっていることを確認
     */
    public function testAttendanceShowName()
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

        // 勤怠詳細画面に遷移
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 名前がログインユーザーの氏名になっていることを確認
        $response->assertSee($user->name);

        Carbon::setTestNow();
    }

    /**
     * 勤怠詳細画面の「日付」が選択した日付になっている
     * 日付が選択した日付になっていることを確認
     */
    public function testAttendanceShowDate()
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

        // 勤怠詳細画面に遷移
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 日付が選択した日付になっていることを確認
        $response->assertSee(Carbon::parse($attendance->work_date)->format('Y年'));
        $response->assertSee(Carbon::parse($attendance->work_date)->format('n月j日'));

        Carbon::setTestNow();
    }

    /**
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
     * 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致していることを確認
     */
    public function testAttendanceShowTimeInAndOut()
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

        // 勤怠詳細画面に遷移
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致していることを確認
        $response->assertSee(Carbon::parse($attendance->started_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendance->ended_at)->format('H:i'));

        Carbon::setTestNow();
    }

    /**
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致している
     * 「休憩」にて記されている時間がログインユーザーの打刻と一致していることを確認
     */
    public function testAttendanceShowBreakTimeInAndOut()
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
            'break_end_at' => now()->addHour(3)->addMinute(30),
        ]);
        $attendanceBreak2 = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now()->addHour(6),
            'break_end_at' => now()->addHour(6)->addMinute(30),
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠詳細画面に遷移
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 「休憩」にて記されている時間がログインユーザーの打刻と一致していることを確認
        $response->assertSee(Carbon::parse($attendanceBreak->break_start_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceBreak->break_end_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceBreak2->break_start_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceBreak2->break_end_at)->format('H:i'));

        Carbon::setTestNow();
    }
}
