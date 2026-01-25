<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            AttendanceSeeder::class,
            AttendanceBreakSeeder::class,
            AttendanceRequestSeeder::class,
            AttendanceRequestBreakSeeder::class,
            AttendanceApprovalSeeder::class,
        ]);
        // \App\Models\User::factory(10)->create();
    }
}
