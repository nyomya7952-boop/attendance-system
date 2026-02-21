<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Role as RoleModel;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;

class AttendanceBreakInTest extends TestCase
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
     * 休憩入ボタンが正しく機能する
     * 画面上に「休憩入」ボタンが表示され、処理後に画面上に表示されるステータスが「休憩中」になる
     */
    public function testBreakInButton()
    {
        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
            'ended_at' => null,
            'total_break_minutes' => null,
            'total_work_minutes' => null,
            'status' => AttendanceStatus::CLOCKED_IN,
            'remarks' => 'テスト',
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 休憩入ボタンが存在することを確認
        $response->assertSee('休憩入');

        // 休憩入ボタンをクリック
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩中');
    }

    /**
     * 休憩は一日に何回でもできる
     * 画面上に「休憩入」ボタンが表示される
     */
    public function testBreakInButtonCreatesBreakRecordManyTimes()
    {
        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
            'ended_at' => null,
            'total_break_minutes' => null,
            'total_work_minutes' => null,
            'status' => AttendanceStatus::CLOCKED_IN,
            'remarks' => 'テスト',
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 休憩入ボタンをクリック
        $attendanceBreakStartedAt1 = now();
        \Carbon\Carbon::setTestNow($attendanceBreakStartedAt1);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩中');

        // 休憩戻ボタンをクリック
        $attendanceBreakEndedAt1 = $attendanceBreakStartedAt1->copy()->addMinute();
        \Carbon\Carbon::setTestNow($attendanceBreakEndedAt1);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));
        \Carbon\Carbon::setTestNow();

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');

        // 休憩入ボタンをクリック
        $attendanceBreakStartedAt2 = $attendanceBreakEndedAt1->copy()->addMinute();
        \Carbon\Carbon::setTestNow($attendanceBreakStartedAt2);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩中');

        // 休憩戻ボタンをクリック
        $attendanceBreakEndedAt2 = $attendanceBreakStartedAt2->copy()->addMinute();
        \Carbon\Carbon::setTestNow($attendanceBreakEndedAt2);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));
        \Carbon\Carbon::setTestNow();

        // AttendanceBreakテーブルに2件のデータが存在することを確認
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => $attendanceBreakStartedAt1,
        ]);
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => $attendanceBreakStartedAt2,
        ]);
    }

    /**
     * 休憩戻ボタンが正しく機能する
     * 休憩戻ボタンが表示され、処理後にステータスが「出勤中」に変更される
     */
    public function testBreakOutButton()
    {
        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
            'ended_at' => null,
            'total_break_minutes' => null,
            'total_work_minutes' => null,
            'status' => AttendanceStatus::CLOCKED_IN,
            'remarks' => 'テスト',
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 休憩入ボタンをクリック
        $attendanceBreakStartedAt1 = now();
        \Carbon\Carbon::setTestNow($attendanceBreakStartedAt1);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩中');

        // 休憩戻ボタンをクリック
        $attendanceBreakEndedAt1 = $attendanceBreakStartedAt1->copy()->addMinute();
        \Carbon\Carbon::setTestNow($attendanceBreakEndedAt1);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));
        \Carbon\Carbon::setTestNow();

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');
        $response->assertSee('退勤');
    }

    /**
     * 休憩戻は一日に何回でもできる
     * 画面上に「休憩戻」ボタンが表示される
     */
    public function testBreakInButtonCreatesBreakRecordEndTime()
    {
        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
            'ended_at' => null,
            'total_break_minutes' => null,
            'total_work_minutes' => null,
            'status' => AttendanceStatus::CLOCKED_IN,
            'remarks' => 'テスト',
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 休憩入ボタンをクリック
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_start',
        ]);
        $attendanceBreakStartedAt1 = now();
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩中');

        // 休憩戻ボタンをクリック
        $attendanceBreakEndedAt1 = $attendanceBreakStartedAt1->copy()->addMinute();
        \Carbon\Carbon::setTestNow($attendanceBreakEndedAt1);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');

        // 休憩入ボタンをクリック
        $attendanceBreakStartedAt2 = $attendanceBreakEndedAt1->copy()->addMinute();
        \Carbon\Carbon::setTestNow($attendanceBreakStartedAt2);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩中');
        $response->assertSee('休憩戻');

        // 休憩戻ボタンをクリック
        $attendanceBreakEndedAt2 = $attendanceBreakStartedAt2->copy()->addMinute();
        \Carbon\Carbon::setTestNow($attendanceBreakEndedAt2);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));
        \Carbon\Carbon::setTestNow();

        // AttendanceBreakテーブルに2件のデータが存在することを確認
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => $attendanceBreakStartedAt1,
        ]);
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start_at' => $attendanceBreakStartedAt2,
        ]);
    }

    /**
     * 休憩時刻が勤怠一覧画面で確認できる
     * 勤怠一覧画面に休憩時刻が正確に記録されている
     */
    public function testBreakInAttendanceListList()
    {
        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
            'ended_at' => null,
            'total_break_minutes' => null,
            'total_work_minutes' => null,
            'status' => AttendanceStatus::CLOCKED_IN,
            'remarks' => 'テスト',
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 休憩入ボタンをクリック
        $attendanceBreakStartedAt1 = now();
        \Carbon\Carbon::setTestNow($attendanceBreakStartedAt1);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩中');

        // 休憩戻ボタンをクリック
        $attendanceBreakEndedAt1 = $attendanceBreakStartedAt1->copy()->addMinute();
        \Carbon\Carbon::setTestNow($attendanceBreakEndedAt1);
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));
        \Carbon\Carbon::setTestNow();

        // 勤怠一覧画面に遷移
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 勤怠一覧画面に休憩日付が正確に記録されていることを確認
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $response->assertSee($attendanceBreakStartedAt1->format('m/d') . '(' . $weekdays[$attendanceBreakStartedAt1->dayOfWeek] . ')');
        $breakMinutes = $attendanceBreakEndedAt1->diffInMinutes($attendanceBreakStartedAt1);
        $breakHours = intdiv($breakMinutes, 60);
        $breakRemainder = $breakMinutes % 60;
        $response->assertSee($breakHours . ':' . str_pad((string) $breakRemainder, 2, '0', STR_PAD_LEFT));
    }
}
