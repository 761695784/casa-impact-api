<?php

namespace Database\Factories;

use App\Enums\Region;
use App\Models\ImpactIndicator;
use App\Models\ImpactValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImpactValueFactory extends Factory
{
    protected $model = ImpactValue::class;

    public function definition(): array
    {
        return [
            'impact_indicator_id' => ImpactIndicator::factory(),
            'valeur' => fake()->randomFloat(2, 10, 5000),
            'periode' => (string) fake()->numberBetween(2022, 2026),
            'region' => fake()->optional()->randomElement(Region::cases())?->value,
        ];
    }
}
