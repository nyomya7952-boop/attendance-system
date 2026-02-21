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
use App\Models\AttendanceRequest;
use App\Enums\AttendanceRequestStatus;
use App\Models\AttendanceRequestBreak;
use Illuminate\Support\Carbon;

class AttendanceUpdateTest extends TestCase
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
     * 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
     *「出勤時間が不適切な値です」というバリデーションメッセージが表示されることを確認
     */
    public function testAttendanceUpdateTimeInAndOut()
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

        // 出勤時間に表示されている値を、退勤時間より後の時間に修正し、更新ボタンを押下する
        $response = $this->post(route('attendance.update', $attendance->id), [
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
    public function testAttendanceUpdateBreakTimeInAfterEndedAt()
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

        // 休憩開始時間が退勤時間より後になっているデータを送信
        $response = $this->post(route('attendance.update', $attendance->id), [
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
    public function testAttendanceUpdateBreakTimeOutAfterEndedAt()
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

        // 休憩終了時間が退勤時間より後になっているデータを送信
        $response = $this->post(route('attendance.update', $attendance->id), [
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
    public function testAttendanceUpdateRemarksRequired()
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

        // 備考欄が未入力のデータを送信
        $response = $this->post(route('attendance.update', $attendance->id), [
            'remarks' => '',
        ]);
        $response->assertStatus(302);

        // 「備考を記入してください」というバリデーションメッセージが表示されることを確認
        $response->assertSessionHasErrors([
            'remarks' => '備考を記入してください',
        ]);

        Carbon::setTestNow();
    }

    /**
     * 修正申請処理が実行される
     *修正申請が実行され、管理者の承認画面と申請一覧画面に表示される
     */
    public function testAttendanceUpdateRequest()
    {
        // 前月の勤怠データを作成
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
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
            'break_start_at' => now()->addHour(1),
            'break_end_at' => now()->addHour(2),
        ]);

        // 管理者ユーザを作成
        $adminUser = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠詳細画面に遷移
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 休憩終了時間が退勤時間より後になっているデータを送信
        $response = $this->post(route('attendance.update', $attendance->id), [
            'started_at' => $attendance->started_at->format('H:i'),
            'ended_at' => $attendance->ended_at->format('H:i'),
            'remarks' => '修正しました',
            'breaks' => [
                [
                    'break_start_at' => $attendance->started_at->copy()->addHour(1)->format('H:i'),
                    'break_end_at' => $attendance->started_at->copy()->addHour(2)->format('H:i'),
                ],
            ],
        ]);
        $response->assertStatus(302);

        // 次月に申請する
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));
        // 申請リンクを押下して勤怠を申請する
        $response = $this->get(route('request.list'));
        $response->assertStatus(302);

        // ログアウト
        $this->post(route('logout'));

        // 管理者ユーザでログイン
        $this->actingAs($adminUser);

        // 申請一覧画面に遷移
        $response = $this->get(route('admin.request.list'));
        $response->assertStatus(200);

        // 申請一覧画面に申請データが表示されることを確認
        $response->assertSee($user->name);
        $response->assertSee('承認待ち');
        $response->assertSee(Carbon::parse($attendance->work_date)->format('Y/m/d'));
        $response->assertSee('修正しました');
        $response->assertSee(Carbon::parse($attendance->created_at)->format('Y/m/d'));

        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)->first();

        // 申請詳細リンクを押下して修正申請承認画面に遷移
        $response = $this->get(route('admin.request.approve.show', $attendanceRequest->id));
        $response->assertStatus(200);

        // 修正申請承認画面に申請データが表示されることを確認
        $response->assertSee($user->name);
        $response->assertSee(Carbon::parse($attendance->work_date)->format('Y年'));
        $response->assertSee(Carbon::parse($attendance->work_date)->format('n月j日'));
        $response->assertSee($attendance->started_at->format('H:i'));
        $response->assertSee($attendance->ended_at->format('H:i'));
        $response->assertSee('修正しました');
        $response->assertSee(Carbon::parse($attendanceBreak->break_start_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceBreak->break_end_at)->format('H:i'));

        Carbon::setTestNow();
    }

    /**
     * 「承認待ち」にログインユーザーが行った申請が全て表示されていること
     * 申請一覧に自分の申請が全て表示されている
     */
    public function testAttendanceUpdateRequestAll()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
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
            'break_start_at' => now()->addHour(1),
            'break_end_at' => now()->addHour(2),
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->addDay(1)->format('Y-m-d'),
            'started_at' => now()->addDay(1),
            'ended_at' => now()->addDay(1)->addHour(8),
            'total_break_minutes' => 60,
            'total_work_minutes' => 420,
            'remarks' => 'テスト2',
        ]);
        $attendanceBreak2 = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_start_at' => now()->addDay(1)->addHour(1),
            'break_end_at' => now()->addDay(1)->addHour(2),
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠詳細画面に遷移
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 次月に申請する
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // 申請リンクを押下して勤怠を申請する
        $response = $this->get(route('request.list'));
        $response->assertStatus(302);

        // 申請一覧画面に遷移
        $response = $this->get(route('request.list'));
        $response->assertStatus(200);

        // 申請一覧画面に申請データが表示されることを確認
        $response->assertSee($user->name);
        $response->assertSee('承認待ち');
        $response->assertSee(Carbon::parse($attendance->work_date)->format('Y/m/d'));
        $response->assertSee(Carbon::parse($attendance2->work_date)->format('Y/m/d'));
        $response->assertSee('テスト');
        $response->assertSee('テスト2');
        $response->assertSee(Carbon::parse($attendance->created_at)->format('Y/m/d'));
        $response->assertSee(Carbon::parse($attendance2->created_at)->format('Y/m/d'));

        Carbon::setTestNow();
    }

    /**
     * 「承認済み」に管理者が承認した修正申請が全て表示されている
     * 承認済みに管理者が承認した申請が全て表示されている
     */
    public function testAttendanceUpdateRequestApproved()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
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
            'break_start_at' => now()->addHour(1),
            'break_end_at' => now()->addHour(2),
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->addDay(1)->format('Y-m-d'),
            'started_at' => now()->addDay(1),
            'ended_at' => now()->addDay(1)->addHour(8),
            'total_break_minutes' => 60,
            'total_work_minutes' => 420,
            'remarks' => 'テスト2',
        ]);
        $attendanceBreak2 = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_start_at' => now()->addDay(1)->addHour(1),
            'break_end_at' => now()->addDay(1)->addHour(2),
        ]);

        // 管理者ユーザを作成
        $adminUser = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠詳細画面に遷移
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 次月に申請する
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // 申請リンクを押下して勤怠を申請する
        $response = $this->get(route('request.list'));
        $response->assertStatus(302);

        // 申請一覧画面に遷移
        $response = $this->get(route('request.list'));
        $response->assertStatus(200);

        // ログアウト
        $this->post(route('logout'));

        // 管理者ユーザでログイン
        $this->actingAs($adminUser);

        // 申請一覧画面に遷移
        $response = $this->get(route('admin.request.list'));
        $response->assertStatus(200);

        // 承認画面を開く
        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)->first();
        $response = $this->get(route('admin.request.approve.show', $attendanceRequest->id));
        $response->assertStatus(200);

        // 承認ボタンを押下
        $response = $this->post(route('admin.request.approve', $attendanceRequest->id));
        $response->assertStatus(302);

        // 申請一覧画面に遷移
        $response = $this->get(route('request.list'));
        $response->assertStatus(200);

        // 承認済み一覧に遷移
        $response = $this->get(route('admin.request.list', ['tab' => 'approved']));
        $response->assertStatus(200);

        // 承認済み一覧に承認データが表示されることを確認
        $response->assertSee($user->name);
        $response->assertSee('承認済み');
        $response->assertSee(Carbon::parse($attendance->work_date)->format('Y/m/d'));
        $this->assertNotNull(Carbon::parse($attendance2->work_date)->format('Y/m/d'));
        $response->assertSee('テスト');
        $this->assertNotNull('テスト2');

        // 再度承認画面を開く
        $attendanceRequest2 = AttendanceRequest::where('attendance_id', $attendance2->id)->first();
        $response = $this->get(route('admin.request.approve.show', $attendanceRequest2->id));
        $response->assertStatus(200);

        // 承認ボタンを押下
        $response = $this->post(route('admin.request.approve', $attendanceRequest2->id));
        $response->assertStatus(302);

        // 申請一覧画面に遷移
        $response = $this->get(route('request.list'));
        $response->assertStatus(200);

        // 承認済み一覧に遷移
        $response = $this->get(route('admin.request.list', ['tab' => 'approved']));
        $response->assertStatus(200);

        // 承認済み一覧に承認データが表示されることを確認
        $response->assertSee($user->name);
        $response->assertSee('承認済み');
        $response->assertSee(Carbon::parse($attendance->work_date)->format('Y/m/d'));
        $response->assertSee(Carbon::parse($attendance2->work_date)->format('Y/m/d'));
        $response->assertSee('テスト');
        $response->assertSee('テスト2');

        Carbon::setTestNow();
    }

    /**
     *  各申請の「詳細」を押下すると勤怠詳細画面に遷移する
     *  勤怠詳細画面に遷移する
     */
    public function testAttendanceUpdateRequestDetail()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
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
            'break_start_at' => now()->addHour(1),
            'break_end_at' => now()->addHour(2),
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->addDay(1)->format('Y-m-d'),
            'started_at' => now()->addDay(1),
            'ended_at' => now()->addDay(1)->addHour(8),
            'total_break_minutes' => 60,
            'total_work_minutes' => 420,
            'remarks' => 'テスト2',
        ]);
        $attendanceBreak2 = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_start_at' => now()->addDay(1)->addHour(1),
            'break_end_at' => now()->addDay(1)->addHour(2),
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠詳細画面に遷移
        $response = $this->get(route('attendance.detail', $attendance->id));
        $response->assertStatus(200);

        // 次月に申請する
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // 申請リンクを押下して勤怠を申請する
        $response = $this->get(route('request.list'));
        $response->assertStatus(302);

        // 申請一覧画面に遷移
        $response = $this->get(route('request.list'));
        $response->assertStatus(200);

        // 申請詳細リンクを押下して修正申請承認画面に遷移
        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)->first();
        $response = $this->get(route('attendance.detail', $attendanceRequest->attendance_id));
        $response->assertStatus(200);

        // 勤怠詳細画面に遷移することを確認
        $response->assertSee(Carbon::parse($attendance->work_date)->format('Y年'));
        $response->assertSee(Carbon::parse($attendance->work_date)->format('n月j日'));
        $response->assertSee(Carbon::parse($attendance->started_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendance->ended_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceBreak->break_start_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceBreak->break_end_at)->format('H:i'));
        $response->assertSee('*承認待ちのため修正はできません。');
        Carbon::setTestNow();
    }
}