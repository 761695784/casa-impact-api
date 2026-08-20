<?php

namespace Database\Factories;

use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Utilisée uniquement pour des tests génériques hors DomainsSeeder — en
 * pratique, les 6 vrais domaines viennent toujours de DomainsSeeder (voir
 * règle "aucune donnée fictive" : cette factory ne sert pas à peupler de
 * fausses données en dehors du contexte des tests automatisés).
 *
 * @extends Factory<Domain>
 */
class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        $nom = fake()->unique()->words(2, true);

        return [
            'nom' => $nom,
            'slug' => Str::slug($nom).'-'.fake()->unique()->numberBetween(1, 100000),
            'description' => fake()->optional()->sentence(),
            'ordre' => fake()->numberBetween(1, 10),
            'statut' => 'actif',
        ];
    }
}
