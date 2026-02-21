<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Role as RoleModel;
use App\Enums\Role as RoleEnum;
use App\Models\User;

class AttendanceClockTest extends TestCase
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
     * 勤怠登録画面に表示される日付と時間が正しいことを確認
     */
    public function testAttendanceDateAndTime()
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
        $response->assertSee(now()->format('Y年n月j日(' . ['日', '月', '火', '水', '木', '金', '土'][now()->format('w')] . ')'));
        $response->assertSee(now()->format('H:i'));
    }
}