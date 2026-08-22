<?php

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return [
            'nom' => fake()->unique()->company(),
            'description' => fake()->paragraph(),
            'lien' => fake()->url(),
            'type' => fake()->randomElement(['institutionnel', 'financier', 'technique', 'media']),
            'statut' => 'actif',
            'ordre' => 0,
        ];
    }
}
