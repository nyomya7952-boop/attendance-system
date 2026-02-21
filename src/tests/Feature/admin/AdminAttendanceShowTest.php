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

class AdminAttendanceShowTest extends TestCase
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
     * 勤怠詳細画面に表示されるデータが選択したものになっている
     * 詳細画面の内容が選択した情報と一致する
     */
    public function testAdminAttendanceShowDate()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
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
        $this->actingAs($admin);

        // 勤怠詳細画面に遷移
        $response = $this->get(route('admin.attendance.show', $attendance->id));
        $response->assertStatus(200);

        // 日付が選択した日付になっていることを確認
        $response->assertSee($user->name);
        $response->assertSee(Carbon::parse($attendance->work_date)->format('Y年'));
        $response->assertSee(Carbon::parse($attendance->work_date)->format('n月j日'));
        $response->assertSee(Carbon::parse($attendance->started_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendance->ended_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceBreak->break_start_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceBreak->break_end_at)->format('H:i'));
        $response->assertSee($attendance->remarks);

        Carbon::setTestNow();
    }

    /**
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     *「出勤時間が不適切な値です」というバリデーションメッセージが表示されることを確認
     */
    public function testAdminAttendanceUpdateTimeInAndOut()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
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
        $this->actingAs($admin);

        // 勤怠詳細画面に遷移
        $response = $this->get(route('admin.attendance.show', $attendance->id));
        $response->assertStatus(200);

        // 出勤時間に表示されている値を、退勤時間より後の時間に修正し、更新ボタンを押下する
        $response = $this->post(route('admin.attendance.update', $attendance->id), [
            'started_at' => $attendance->ended_at->copy()->addHour(1)->format('H:i'),
            'ended_at' => $attendance->ended_at->format('H:i'),
            'remarks' => 'テスト',
        ]);
        $response->assertStatus(302);

        // 「出勤時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors([
            'started_at' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);

        Carbon::setTestNow();
    }

    /**
     * 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
     *「休憩時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function testAdminAttendanceUpdateBreakTimeInAfterEndedAt()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
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
        $this->actingAs($admin);

        // 勤怠詳細画面に遷移
        $response = $this->get(route('admin.attendance.show', $attendance->id));
        $response->assertStatus(200);

        // 休憩開始時間が退勤時間より後になっているデータを送信
        $response = $this->post(route('admin.attendance.update', $attendance->id), [
            'started_at' => $attendance->started_at->format('H:i'),
            'ended_at' => $attendance->ended_at->format('H:i'),
            'remarks' => 'テスト',
            'breaks' => [
                [
                    'break_start_at' => $attendance->ended_at->copy()->addHour(1)->format('H:i'),
                    'break_end_at' => $attendance->ended_at->copy()->addHour(2)->format('H:i'),
                ],
            ],
        ]);
        $response->assertStatus(302);

        // 「休憩時間が不適切な値です」というバリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors([
            'breaks.0.break_start_at' => '休憩時間が不適切な値です',
        ]);

        Carbon::setTestNow();
    }

    /**
     * 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
     *「休憩時間もしくは退勤時間が不適切な値です」というバリデーションメッセージが表示される
     */
    public function testAdminAttendanceUpdateBreakTimeOutAfterEndedAt()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
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
        $this->actingAs($admin);

        // 勤怠詳細画面に遷移
        $response = $this->get(route('admin.attendance.show', $attendance->id));
        $response->assertStatus(200);

        // 休憩終了時間が退勤時間より後になっているデータを送信
        $response = $this->post(route('admin.attendance.update', $attendance->id), [
            'started_at' => $attendance->started_at->format('H:i'),
            'ended_at' => $attendance->ended_at->format('H:i'),
            'remarks' => 'テスト',
            'breaks' => [
                [
                    'break_start_at' => $attendance->started_at->copy()->addHour(1)->format('H:i'),
                    'break_end_at' => $attendance->ended_at->copy()->addHour(1)->format('H:i'),
                ],
            ],
        ]);
        $response->assertStatus(302);

        // 「休憩時間が不適切な値です」というバリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors([
            'breaks.0.break_end_at' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);

        Carbon::setTestNow();
    }

    /**
     * 備考欄が未入力の場合のエラーメッセージが表示される
     *「備考を記入してください」というバリデーションメッセージが表示される
     */
    public function testAdminAttendanceUpdateRemarksRequired()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
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
        $this->actingAs($admin);

        // 勤怠詳細画面に遷移
        $response = $this->get(route('admin.attendance.show', $attendance->id));
        $response->assertStatus(200);

        // 備考欄が未入力のデータを送信
        $response = $this->post(route('admin.attendance.update', $attendance->id), [
            'remarks' => '',
        ]);
        $response->assertStatus(302);

        // 「備考を記入してください」というバリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください',
        ]);

        Carbon::setTestNow();
    }
}
