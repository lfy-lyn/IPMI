<?php

namespace Database\Factories;

use App\Models\IncidentalActivity;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class IncidentalActivityFactory extends Factory
{
    protected $model = IncidentalActivity::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'start_date' => fake()->dateTimeBetween('now', '+1 month'),
            'end_date' => fake()->optional()->dateTimeBetween('+1 month', '+2 months'),
            'division_id' => null,
            'attendance_mode' => 'per_event',
            'status' => fake()->randomElement(['upcoming', 'ongoing', 'completed']),
            'created_by' => User::factory(),
        ];
    }

    public function perDay(): static
    {
        return $this->state(fn () => ['attendance_mode' => 'per_day']);
    }
}
