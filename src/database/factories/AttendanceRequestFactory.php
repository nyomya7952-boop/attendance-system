<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceRequestFactory extends Factory
{
    protected $model = AttendanceRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $requestedStartedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $requestedEndedAt = (clone $requestedStartedAt)->modify('+8 hours');

        return [
            'attendance_id' => Attendance::factory(),
            'requested_work_date' => $requestedStartedAt->format('Y-m-d'),
            'requested_started_at' => $requestedStartedAt,
            'requested_ended_at' => $requestedEndedAt,
            'remarks' => $this->faker->sentence(),
            'requested_by' => User::factory(),
            'approver_id' => User::factory(),
            'status' => $this->faker->randomElement(['pending', 'approved']),
        ];
    }
}

