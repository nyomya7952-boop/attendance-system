<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceApproval;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceApprovalFactory extends Factory
{
    protected $model = AttendanceApproval::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $finalStartedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $finalEndedAt = (clone $finalStartedAt)->modify('+8 hours');

        return [
            'attendance_id' => Attendance::factory(),
            'attendance_request_id' => AttendanceRequest::factory(),
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'status' => $this->faker->randomElement(['approved', 'rejected']),
            'final_started_at' => $finalStartedAt,
            'final_ended_at' => $finalEndedAt,
            'final_break_minutes' => $this->faker->numberBetween(0, 60),
            'final_work_minutes' => $this->faker->numberBetween(240, 480),
        ];
    }
}

