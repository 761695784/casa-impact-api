<?php

namespace Database\Factories;

use App\Models\ProgramType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProgramType>
 */
class ProgramTypeFactory extends Factory
{
    protected $model = ProgramType::class;

    public function definition(): array
    {
        $nom = fake()->unique()->words(2, true);

        return [
            'nom' => $nom,
            'slug' => Str::slug($nom).'-'.fake()->unique()->numberBetween(1, 100000),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
