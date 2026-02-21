<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ロールが存在するか確認
        $roles = Role::all();
        if ($roles->isEmpty()) {
            $this->command->warn('ロールが存在しません。先にRoleSeederを実行してください。');
            return;
        }

        // 管理者ユーザーを作成
        $adminRole = Role::where('id', 2)->first(); // 管理者ロール
        if ($adminRole) {
            // 既存の管理者ユーザーが存在しない場合のみ作成
            if (!User::where('role_id', 2)->exists()) {
                User::factory()->create([
                    'name' => '管理者',
                    'email' => 'admin@example.com',
                    'password' => Hash::make('password'),
                    'role_id' => 2,
                ]);

                // 追加の管理者ユーザーを2名作成
                User::factory()->count(2)->create([
                    'role_id' => 2,
                ]);
            }
        }

        // 一般ユーザーを作成
        $userRole = Role::where('id', 1)->first(); // 一般ユーザーロール
        if ($userRole) {
            // 既存の一般ユーザーが存在しない場合のみ作成
            if (!User::where('role_id', 1)->exists()) {
                User::factory()->create([
                    'name' => 'テストユーザー',
                    'email' => 'user@example.com',
                    'password' => Hash::make('password'),
                    'role_id' => 1,
                ]);

                // 追加の一般ユーザーを10名作成
                User::factory()->count(10)->create([
                    'role_id' => 1,
                ]);
            }
        }
    }
}

