<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'content' => fake()->paragraph(3),
            'target_type' => fake()->randomElement(['all', 'division', 'pengurus']),
            'division_id' => null,
            'status' => 'published',
            'published_at' => now(),
            'created_by' => User::factory(),
        ];
    }
}
