<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Role as RoleModel;
use App\Enums\Role as RoleEnum;
use App\Models\User;

class AttendanceStatusTest extends TestCase
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
     * 勤務外の場合、勤怠ステータスが正しく表示される
     *
     * @return void
     */
    public function testOutsideWorkStatus()
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
        $response->assertSee('勤務外');
    }

    /**
     * 出勤中の場合、勤怠ステータスが正しく表示される
     *
     * @return void
     */
    public function testClockedInStatus()
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
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');
    }

    /**
     * 休憩中の場合、勤怠ステータスが正しく表示される
     *
     * @return void
     */
    public function testOnBreakStatus()
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
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');

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
     * 退勤済の場合、勤怠ステータスが正しく表示される
     *
     * @return void
     */
    public function testClockedOutStatus()
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
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');

        // 休憩入ボタンをクリック
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_start',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('休憩中');

        // 休憩戻ボタンをクリック
        $response = $this->post(route('attendance.store'), [
            'action' => 'break_end',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('出勤中');

        // 退勤ボタンをクリック
        $response = $this->post(route('attendance.store'), [
            'action' => 'clock_out',
        ]);
        $response->assertStatus(302);
        $response->assertRedirect(route('attendance.index'));

        // 勤怠登録画面に遷移
        $response = $this->get(route('attendance.index'));
        $response->assertSee('退勤済');
    }
}
