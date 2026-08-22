<?php

namespace Database\Factories;

use App\Models\ImpactIndicator;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImpactIndicatorFactory extends Factory
{
    protected $model = ImpactIndicator::class;

    public function definition(): array
    {
        return [
            'libelle' => fake()->randomElement([
                'Jeunes formés',
                'Emplois créés',
                'Entreprises accompagnées',
                'Candidatures reçues',
                'Bénéficiaires directs',
            ]),
            'unite' => fake()->randomElement(['personnes', 'entreprises', 'FCFA', null]),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
