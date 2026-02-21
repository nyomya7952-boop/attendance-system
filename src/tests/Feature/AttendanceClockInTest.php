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

class AttendanceClockInTest extends TestCase
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
     * 出勤ボタンが正しく機能する
     * 画面上に「出勤」ボタンが表示され、処理後に画面上に表示されるステータスが「勤務中」になる
     *
     * @return void
     */
    public function testClockInButton()
    {
        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 出勤ボタンが存在することを確認
        $response->assertSee('出勤');

        // 出勤ボタンをクリック
        $response = $this->post(route('attendance.store'), [
            'action' => 'clock_in',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');
    }

    /**
     * 出勤は一日一回のみできる
     * 画面上に「出勤」ボタンが表示されないことを確認
     */
    public function testClockInButtonCreatesAttendanceRecord()
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
            'status' => AttendanceStatus::CLOCKED_OUT,
            'ended_at' => now(),
            'total_break_minutes' => 0,
            'total_work_minutes' => 0,
            'remarks' => 'テスト',
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 出勤ボタンが存在しないことを確認
        $response->assertDontSee('出勤');
    }

    /**
     * 出勤時刻が勤怠一覧画面で確認できる
     * 勤怠一覧画面に出勤時刻が正確に記録されている
     */
    public function testClockInAttendanceListList()
    {
        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertStatus(200);

        // 出勤ボタンをクリック
        $response = $this->post(route('attendance.store'), [
            'action' => 'clock_in',
        ]);
        $attendanceStartedAt = now();
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠一覧画面に遷移
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 勤怠一覧画面に出勤日付が正確に記録されていることを確認
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $response->assertSee($attendanceStartedAt->format('m/d') . '(' . $weekdays[$attendanceStartedAt->dayOfWeek] . ')');
        $response->assertSee($attendanceStartedAt->format('H:i'));
    }
}
