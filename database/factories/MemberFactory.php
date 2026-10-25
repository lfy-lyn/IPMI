<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Division;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'nis' => fake()->unique()->numerify('########'),
            'full_name' => fake()->name(),
            'division_id' => Division::factory(),
            'phone' => fake()->optional()->numerify('08##########'),
            'status' => 'active',
        ];
    }
}
