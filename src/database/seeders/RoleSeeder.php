<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['id' => 1, 'name' => '一般ユーザー'],
            ['id' => 2, 'name' => '管理者'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert([
                'id' => $role['id'],
                'name' => $role['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

