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
use Carbon\Carbon;
use App\Enums\AttendanceStatus;

class AdminStaffListTest extends TestCase
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
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     * 全ての一般ユーザーの氏名とメールアドレスが正しく表示されていることを確認
     */
    public function testAdminStaffList()
    {
        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);
        $user2 = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);
        // ログイン
        $this->actingAs($admin);

        // スタッフ一覧画面に遷移
        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);

        // 全ての一般ユーザーの氏名とメールアドレスが正しく表示されていることを確認
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee($user2->name);
        $response->assertSee($user2->email);
    }

    /**
     * 一般ユーザーの勤怠情報が正しく表示される
     * 勤怠情報が正確に表示される
     */
    public function testAdminStaffAttendanceList()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now()->format('H:i'),
            'ended_at' => now()->addHour(8)->format('H:i'),
            'total_break_minutes' => 60,
            'total_work_minutes' => 420,
        ]);
        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->addDay()->format('Y-m-d'),
            'started_at' => now()->addDay()->format('H:i'),
            'ended_at' => now()->addDay()->addHour(9)->format('H:i'),
            'total_break_minutes' => 60,
            'total_work_minutes' => 480,
        ]);

        $attendanceBreak = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now()->addHour(1)->format('H:i'),
            'break_end_at' => now()->addHour(2)->format('H:i'),
        ]);
        $attendanceBreak2 = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance2->id,
            'break_start_at' => now()->addDay()->addHour(3)->format('H:i'),
            'break_end_at' => now()->addDay()->addHour(4)->format('H:i'),
        ]);

        // ログイン
        $this->actingAs($admin);

        // スタッフ一覧画面に遷移
        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);

        // 全ての一般ユーザーの勤怠一覧が正しく表示されていることを確認
        $response->assertSee($user->name);
        $response->assertSee($user->email);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('admin.staff.attendance', $user->id));
        $response->assertStatus(200);

        // 勤怠情報が正確に表示されていることを確認
        $response->assertSee($user->name . 'さんの勤怠');
        $response->assertSee(now()->format('Y/m'));
        $response->assertSee(now()->addDay()->format('Y/m'));
        $response->assertSee(Carbon::parse($attendance->work_date)->format('m/d'));
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

        $response->assertSee(Carbon::parse($attendance2->work_date)->format('m/d'));
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

        Carbon::setTestNow();
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     * 前月の情報が表示されていることを確認
     */
    public function testAdminStaffAttendanceListPreviousMonthButton()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // ログイン
        $this->actingAs($admin);

        // スタッフ一覧画面に遷移
        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('admin.staff.attendance', $user->id));
        $response->assertStatus(200);

        // 「前月」を押下
        $prevMonth = now()->subMonth()->format('Y-m');
        $response = $this->get(route('admin.staff.attendance', $user->id) . '?month=' . $prevMonth);
        $response->assertStatus(200);

        // 前月の情報が表示されていることを確認
        $response->assertSee(Carbon::parse($prevMonth . '-01')->format('Y/m'));

        Carbon::setTestNow();
    }

    /**
     * 「翌月」を押下した時に表示月の前月の情報が表示される
     * 翌月の情報が表示されていることを確認
     */
    public function testAdminStaffAttendanceListNextMonthButton()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // ログイン
        $this->actingAs($admin);

        // スタッフ一覧画面に遷移
        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('admin.staff.attendance', $user->id));
        $response->assertStatus(200);

        // 「翌月」を押下
        $nextMonth = now()->addMonth()->format('Y-m');
        $response = $this->get(route('admin.staff.attendance', $user->id) . '?month=' . $nextMonth);
        $response->assertStatus(200);

        // 翌月の情報が表示されていることを確認
        $response->assertSee(Carbon::parse($nextMonth . '-01')->format('Y/m'));

        Carbon::setTestNow();
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     * その日の勤怠詳細画面に遷移することを確認
     */
    public function testAdminStaffAttendanceListDetailButtonRedirectsToShow()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => now(),
            'status' => AttendanceStatus::CLOCKED_OUT,
            'ended_at' => now()->addHour(9),
            'total_break_minutes' => 60,
            'total_work_minutes' => 480,
            'remarks' => 'テスト',
        ]);

        $attendanceBreak = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => now()->addHour(3),
            'break_end_at' => now()->addHour(3)->addMinute(60),
        ]);

        // ログイン
        $this->actingAs($admin);

        // スタッフ一覧画面に遷移
        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('admin.staff.attendance', $user->id));
        $response->assertStatus(200);

        // 「詳細」を押下
        $response = $this->get(route('admin.attendance.show', $attendance->id));
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

    /**
     * 「詳細」を押下すると、勤怠データが存在しない場合はadmin.attendance.detail.byDateで新規登録フォームが表示される
     * admin.attendance.detail.byDateに遷移することを確認
     */
    public function testAdminStaffAttendanceListDetailButtonShowsByDateForm()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // ログイン
        $this->actingAs($admin);

        // スタッフ一覧画面に遷移
        $response = $this->get(route('admin.staff.list'));
        $response->assertStatus(200);

        // 勤怠一覧画面に遷移
        $workDate = now()->format('Y-m-d');
        $response = $this->get(route('admin.staff.attendance', $user->id));
        $response->assertStatus(200);

        // 「詳細」を押下（勤怠データが存在しないため、admin.attendance.detail.byDateで新規登録フォームが表示される）
        $response = $this->get(route('admin.attendance.detail.byDate', ['date' => $workDate, 'user_id' => $user->id]));
        $response->assertStatus(200);

        // 新規登録フォームが表示されることを確認
        $response->assertSee($user->name);
        $response->assertSee(Carbon::parse($workDate)->format('Y年'));
        $response->assertSee(Carbon::parse($workDate)->format('n月j日'));

        Carbon::setTestNow();
    }
}
