<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        return [
            'division_id' => Division::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'file_path' => null,
            'external_url' => fake()->url(),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
