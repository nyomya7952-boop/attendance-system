<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $startedAt = $this->faker->dateTimeBetween('-1 month', 'now');
        $endedAt = (clone $startedAt)->modify('+8 hours');
        $workDate = $startedAt->format('Y-m-d');

        return [
            'user_id' => User::factory(),
            'work_date' => $workDate,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'status' => $this->faker->randomElement(['draft', 'submitted', 'approved', 'rejected']),
            'total_break_minutes' => $this->faker->numberBetween(0, 60),
            'total_work_minutes' => $this->faker->numberBetween(240, 480),
        ];
    }
}

