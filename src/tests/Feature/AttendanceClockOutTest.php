<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role as RoleModel;
use App\Enums\Role as RoleEnum;
use App\Models\Attendance;
use App\Enums\AttendanceStatus;
use App\Models\AttendanceBreak;

class AttendanceClockOutTest extends TestCase
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
     * 退勤ボタンが正しく機能する
     * 画面上に「退勤」ボタンが表示され、処理後に画面上に表示されるステータスが「退勤済」になる
     *
     */
    public function testClockOutButtonWorksCorrectly()
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

        // 退勤ボタンが存在することを確認
        $response->assertSee('退勤');

        // 退勤ボタンをクリック
        $response = $this->post(route('attendance.store'), [
            'action' => 'clock_out',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('退勤済');
        $response->assertSee('お疲れ様でした。');
        $this->assertNotNull('休憩入');
        $this->assertNotNull('休憩戻');
        $this->assertNotNull('出勤');
    }

    /**
     * 退勤時刻が勤怠一覧画面で確認できる
     * 勤怠一覧画面に退勤時刻が正確に記録されている
     */
    public function testClockOutButtonDoesNotWorkCorrectly()
    {
        // テスト用のユーザを作成
        $user = User::factory()->create([
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        $startedAt = now();
        $endedAt = $startedAt->copy()->addHour();
        $breakStartedAt = $startedAt->copy()->addMinute(2);
        $breakEndedAt = $breakStartedAt->copy()->addMinute();

        // 勤怠記録を作成
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => now()->format('Y-m-d'),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'total_break_minutes' => $breakEndedAt->diffInMinutes($breakStartedAt),
            'total_work_minutes' => $endedAt->diffInMinutes($startedAt) - $breakEndedAt->diffInMinutes($breakStartedAt),
            'status' => AttendanceStatus::CLOCKED_OUT,
            'remarks' => 'テスト',
        ]);

        //休憩記録を作成
        $attendanceBreak = AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start_at' => $breakStartedAt,
            'break_end_at' => $breakEndedAt,
        ]);

        // ログイン
        $this->actingAs($user);

        // 勤怠一覧画面に遷移
        $response = $this->get(route('attendance.list'));
        $response->assertStatus(200);

        // 勤怠一覧画面に休憩日付が正確に記録されていることを確認
        // 日付
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $response->assertSee($startedAt->format('m/d') . '(' . $weekdays[$breakStartedAt->dayOfWeek] . ')');
        // 出勤時刻
        $response->assertSee($startedAt->format('H:i'));
        // 退勤時刻
        $response->assertSee($endedAt->format('H:i'));
        // 休憩時間
        $breakMinutes = $breakEndedAt->diffInMinutes($breakStartedAt);
        $breakHours = intdiv($breakMinutes, 60);
        $breakRemainder = $breakMinutes % 60;
        $response->assertSee($breakHours . ':' . str_pad((string) $breakRemainder, 2, '0', STR_PAD_LEFT));
        // 勤務時間（休憩時間を除外）
        $workMinutes = $endedAt->diffInMinutes($startedAt) - $breakMinutes;
        $workHours = intdiv($workMinutes, 60);
        $workRemainder = $workMinutes % 60;
        $response->assertSee($workHours . ':' . str_pad((string) $workRemainder, 2, '0', STR_PAD_LEFT));
        // 詳細リンク
        $response->assertSee('詳細');
    }
}
