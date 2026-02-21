<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceBreakFactory extends Factory
{
    protected $model = AttendanceBreak::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $breakStartAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $breakEndAt = (clone $breakStartAt)->modify('+30 minutes');

        return [
            'attendance_id' => Attendance::factory(),
            'break_start_at' => $breakStartAt,
            'break_end_at' => $breakEndAt,
        ];
    }
}

