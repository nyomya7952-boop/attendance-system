<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Role as RoleModel;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Models\AttendanceRequest;
use App\Models\AttendanceRequestBreak;
use App\Models\AttendanceApproval;
use App\Enums\AttendanceRequestStatus;
use App\Enums\AttendanceApprovalStatus;
use Carbon\Carbon;

class AdminAttendanceApproveTest extends TestCase
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
     * 承認待ちの修正申請が全て表示されている
     * 全ユーザーの未承認の修正申請が表示されていることを確認
     */
    public function testAdminAttendanceApprove()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

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

        // 承認待ちの修正申請を作成
        $attendanceRequest = AttendanceRequest::factory()->create([
            'requested_by' => $user->id,
            'status' => AttendanceRequestStatus::SUBMITTED,
        ]);
        $attendanceRequestBreak = AttendanceRequestBreak::factory()->create([
            'attendance_request_id' => $attendanceRequest->id,
            'break_start_at' => now()->addHour(2)->format('Y-m-d'),
            'break_end_at' => now()->addHour(3)->format('Y-m-d'),
        ]);
        $attendanceRequestBreak1 = AttendanceRequestBreak::factory()->create([
            'attendance_request_id' => $attendanceRequest->id,
            'break_start_at' => now()->addHour(5)->format('Y-m-d'),
            'break_end_at' => now()->addHour(6)->format('Y-m-d'),
        ]);
        // 承認済みの修正申請を作成
        $attendanceRequest2 = AttendanceRequest::factory()->create([
            'requested_by' => $user2->id,
            'status' => AttendanceRequestStatus::APPROVED,
        ]);
        $attendanceRequestBreak2 = AttendanceRequestBreak::factory()->create([
            'attendance_request_id' => $attendanceRequest2->id,
            'break_start_at' => now()->addHour(3)->format('Y-m-d'),
            'break_end_at' => now()->addHour(4)->format('Y-m-d'),
        ]);

        // ログイン
        $this->actingAs($admin);

        // 修正申請一覧画面に遷移
        $response = $this->get(route('admin.request.list'));
        $response->assertStatus(200);

        // 申請一覧画面に未承認の修正申請が表示されていることを確認
        $response->assertSee($user->name);
        $response->assertSee('承認待ち');
        $response->assertSee(Carbon::parse($attendanceRequest->requested_work_date)->format('Y/m/d'));
        $response->assertSee($attendanceRequest->remarks);
        $response->assertSee(Carbon::parse($attendanceRequest->created_at)->format('Y/m/d'));
        $response->assertDontSee($user2->name);

        Carbon::setTestNow();
    }

    /**
     * 承認済みの修正申請が全て表示されている
     * 全ユーザーの承認済みの修正申請が表示される
     */
    public function testAdminAttendanceApproveApproved()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

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

        // 承認待ちの修正申請を作成
        $attendanceRequest = AttendanceRequest::factory()->create([
            'requested_by' => $user->id,
            'status' => AttendanceRequestStatus::SUBMITTED,
        ]);
        $attendanceRequestBreak = AttendanceRequestBreak::factory()->create([
            'attendance_request_id' => $attendanceRequest->id,
            'break_start_at' => now()->addHour(2)->format('Y-m-d'),
            'break_end_at' => now()->addHour(3)->format('Y-m-d'),
        ]);
        $attendanceRequestBreak1 = AttendanceRequestBreak::factory()->create([
            'attendance_request_id' => $attendanceRequest->id,
            'break_start_at' => now()->addHour(5)->format('Y-m-d'),
            'break_end_at' => now()->addHour(6)->format('Y-m-d'),
        ]);
        // 承認済みの修正申請を作成
        $attendanceRequest2 = AttendanceRequest::factory()->create([
            'requested_by' => $user2->id,
            'status' => AttendanceRequestStatus::APPROVED,
        ]);
        $attendanceRequestBreak2 = AttendanceRequestBreak::factory()->create([
            'attendance_request_id' => $attendanceRequest2->id,
            'break_start_at' => now()->addHour(3)->format('Y-m-d'),
            'break_end_at' => now()->addHour(4)->format('Y-m-d'),
        ]);

        // ログイン
        $this->actingAs($admin);

        // 修正申請一覧画面に遷移
        $response = $this->get(route('admin.request.list'));
        $response->assertStatus(200);

        // 承認済みタブに遷移
        $response = $this->get(route('admin.request.list', ['tab' => 'approved']));
        $response->assertStatus(200);

        // 申請一覧画面に承認済みの修正申請が表示されていることを確認
        $response->assertSee($user2->name);
        $response->assertSee('承認済み');
        $response->assertSee(Carbon::parse($attendanceRequest2->requested_work_date)->format('Y/m/d'));
        $response->assertSee($attendanceRequest2->remarks);
        $response->assertSee(Carbon::parse($attendanceRequest2->created_at)->format('Y/m/d'));
        $response->assertDontSee($user->name);

        Carbon::setTestNow();
    }

    /**
     * 修正申請の詳細内容が正しく表示されている
     * 申請内容が正しく表示されていることを確認
     */
    public function testAdminAttendanceApproveDetail()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // 承認待ちの修正申請を作成
        $attendanceRequest = AttendanceRequest::factory()->create([
            'requested_by' => $user->id,
            'status' => AttendanceRequestStatus::SUBMITTED,
        ]);
        $attendanceRequestBreak = AttendanceRequestBreak::factory()->create([
            'attendance_request_id' => $attendanceRequest->id,
            'break_start_at' => now()->addHour(2)->format('Y-m-d'),
            'break_end_at' => now()->addHour(3)->format('Y-m-d'),
        ]);
        $attendanceRequestBreak1 = AttendanceRequestBreak::factory()->create([
            'attendance_request_id' => $attendanceRequest->id,
            'break_start_at' => now()->addHour(5)->format('Y-m-d'),
            'break_end_at' => now()->addHour(6)->format('Y-m-d'),
        ]);

        // ログイン
        $this->actingAs($admin);

        // 修正申請一覧画面に遷移
        $response = $this->get(route('admin.request.list'));
        $response->assertStatus(200);

        // 申請詳細リンクを押下して修正申請承認画面に遷移
        $response = $this->get(route('admin.request.approve.show', $attendanceRequest->id));
        $response->assertStatus(200);

        // 修正申請承認画面に申請データが表示されることを確認
        $response->assertSee($user->name);
        $response->assertSee(Carbon::parse($attendanceRequest->requested_work_date)->format('Y年'));
        $response->assertSee(Carbon::parse($attendanceRequest->requested_work_date)->format('n月j日'));
        $response->assertSee($attendanceRequest->requested_started_at->format('H:i'));
        $response->assertSee($attendanceRequest->requested_ended_at->format('H:i'));
        $response->assertSee($attendanceRequest->remarks);
        $response->assertSee(Carbon::parse($attendanceRequestBreak->break_start_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceRequestBreak->break_end_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceRequestBreak1->break_start_at)->format('H:i'));
        $response->assertSee(Carbon::parse($attendanceRequestBreak1->break_end_at)->format('H:i'));

        Carbon::setTestNow();
    }

    /**
     * 修正申請の承認処理が正しく行われる
     * 修正申請が承認され、勤怠情報が更新されることを確認
     */
    public function testAdminAttendanceApproveApprove()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0, 0));

        // テスト用のユーザを作成
        $admin = User::factory()->create([
            'role_id' => RoleEnum::ADMIN->value,
        ]);
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // 承認待ちの修正申請を作成
        $attendanceRequest = AttendanceRequest::factory()->create([
            'requested_by' => $user->id,
            'status' => AttendanceRequestStatus::SUBMITTED,
        ]);
        $attendanceRequestBreak = AttendanceRequestBreak::factory()->create([
            'attendance_request_id' => $attendanceRequest->id,
            'break_start_at' => now()->addHour(2)->format('Y-m-d'),
            'break_end_at' => now()->addHour(3)->format('Y-m-d'),
        ]);
        $attendanceRequestBreak1 = AttendanceRequestBreak::factory()->create([
            'attendance_request_id' => $attendanceRequest->id,
            'break_start_at' => now()->addHour(5)->format('Y-m-d'),
            'break_end_at' => now()->addHour(6)->format('Y-m-d'),
        ]);

        // ログイン
        $this->actingAs($admin);

        // 修正申請一覧画面に遷移
        $response = $this->get(route('admin.request.list'));
        $response->assertStatus(200);

        // 申請詳細リンクを押下して修正申請承認画面に遷移
        $response = $this->get(route('admin.request.approve.show', $attendanceRequest->id));
        $response->assertStatus(200);

        // 承認ボタンを押下
        $response = $this->post(route('admin.request.approve', $attendanceRequest->id));
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.request.approve.show', $attendanceRequest->id));
        $response->assertSessionHas('success', '修正申請を承認しました。');

        // リダイレクト先のページにアクセス
        $response = $this->get(route('admin.request.approve.show', $attendanceRequest->id));
        $response->assertStatus(200);

        // 承認されたことを確認
        $response->assertSee('修正申請を承認しました。');
        // 承認済みボタンが表示されていることを確認
        $response->assertSee('承認済み');

        // 勤怠情報が更新されたことを確認
        $attendanceApproval = AttendanceApproval::where('attendance_request_id', $attendanceRequest->id)->first();
        $this->assertEquals(AttendanceApprovalStatus::APPROVED->value, $attendanceApproval->status);
        $attendanceRequest->refresh();
        $this->assertEquals(AttendanceRequestStatus::APPROVED->value, $attendanceRequest->status);

        Carbon::setTestNow();
    }
}
