<?php

namespace Database\Factories;

use App\Models\Domain;
use App\Models\Program;
use App\Models\ProgramType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        $titre = fake()->unique()->sentence(3);
        $dateDebut = fake()->dateTimeBetween('-6 months', '+6 months');

        return [
            'titre' => $titre,
            'slug' => Str::slug($titre).'-'.fake()->unique()->numberBetween(1, 100000),
            'description' => fake()->paragraphs(2, true),
            'statut' => 'brouillon',
            'domain_id' => Domain::factory(),
            'program_type_id' => ProgramType::factory(),
            'date_debut' => $dateDebut,
            'date_fin' => fake()->optional()->dateTimeBetween($dateDebut, '+1 year'),
        ];
    }
}
