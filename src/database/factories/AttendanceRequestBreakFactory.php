<?php

namespace Database\Factories;

use App\Models\AttendanceRequest;
use App\Models\AttendanceRequestsBreak;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRequestBreakFactory extends Factory
{
    protected $model = AttendanceRequestsBreak::class;

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
            'attendance_request_id' => AttendanceRequest::factory(),
            'break_start_at' => $breakStartAt,
            'break_end_at' => $breakEndAt,
        ];
    }
}

