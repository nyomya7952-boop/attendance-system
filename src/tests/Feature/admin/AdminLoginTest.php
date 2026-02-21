<?php

namespace Tests\Feature\admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role as RoleModel;
use Illuminate\Support\Facades\Hash;
use App\Enums\Role as RoleEnum;

class AdminLoginTest extends TestCase
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
     * メールアドレスが未入力の場合、バリデーションメッセージが表示される
     */
    public function testEmailValidationWhenEmpty()
    {
        // ０．テスト用のユーザを登録
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role_id' => RoleEnum::ADMIN->value,
        ]);

        // 1. ログインページを開く
        $response = $this->get(route('admin.login.show'));
        $response->assertStatus(200);

        // 2. メールアドレスを入力せずに他の必要項目を入力する
        // 3. ログインボタンを押す
        $response = $this->post(route('admin.login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        // バリデーションメッセージが表示される
        $response->assertSessionHasErrors(['email']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('メールアドレスを入力してください', $errors->get('email')[0]);
    }

    /**
     * パスワードが未入力の場合、バリデーションメッセージが表示される
     */
    public function testPasswordValidationWhenEmpty()
    {
         // ０．テスト用のユーザを登録
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role_id' => RoleEnum::ADMIN->value,
        ]);

        // 1. ログインページを開く
        $response = $this->get(route('admin.login.show'));
        $response->assertStatus(200);

        // 2. パスワードを入力せずに他の必要項目を入力する
        // 3. ログインボタンを押す
        $response = $this->post(route('admin.login'), [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        // バリデーションメッセージが表示される
        $response->assertSessionHasErrors(['password']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('パスワードを入力してください', $errors->get('password')[0]);
    }

    /**
     * 登録内容と一致しない場合、バリデーションメッセージが表示される
     */
    public function testLoginFailsWithInvalidCredentials()
    {
        // ０．テスト用のユーザを登録
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role_id' => RoleEnum::ADMIN->value,
        ]);

        // 1. ログインページを開く
        $response = $this->get(route('admin.login.show'));
        $response->assertStatus(200);

        // 2. 必要項目を登録されていない情報を入力する
        // 3. ログインボタンを押す
        $response = $this->post(route('admin.login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        // バリデーションメッセージが表示される
        $response->assertSessionHasErrors(['email']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('ログイン情報が登録されていません', $errors->get('email')[0]);
    }

    /**
     * 一般ユーザーの場合、ログインできない
     */
    public function testLoginFailsWithGeneralUser()
    {
        // ０．テスト用のユーザを登録
        $user = User::factory()->create([
            'email' => 'general@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role_id' => RoleEnum::GENERAL_USER->value,
        ]);

        // 1. ログインページを開く
        $response = $this->get(route('admin.login.show'));
        $response->assertStatus(200);

        // 2. 必要項目を登録されていない情報を入力する
        // 3. ログインボタンを押す
        $response = $this->post(route('admin.login'), [
            'email' => 'general@example.com',
            'password' => 'password123',
        ]);

        // バリデーションメッセージが表示される
        $response->assertSessionHasErrors(['email']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('ログイン情報が登録されていません', $errors->get('email')[0]);
    }

    /**
     * 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function testSuccessfulLoginWithValidCredentials()
    {
        // メール認証済みのユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role_id' => RoleEnum::ADMIN->value,
        ]);

        // 1. ログインページを開く
        $response = $this->get(route('admin.login.show'));
        $response->assertStatus(200);

        // 2. 全ての必要項目を入力する
        // 3. ログインボタンを押す
        $response = $this->post(route('admin.login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // ログイン処理が実行される
        $response->assertRedirect(route('admin.attendance.list'));
        $this->assertAuthenticatedAs($user);
    }
}
