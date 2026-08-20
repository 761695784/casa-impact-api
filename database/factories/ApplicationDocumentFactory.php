<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationDocument>
 */
class ApplicationDocumentFactory extends Factory
{
    protected $model = ApplicationDocument::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'type' => fake()->randomElement(['cv', 'piece_identite', 'lettre_motivation']),
            'chemin' => 'application-documents/fake/'.fake()->uuid().'.pdf',
            'nom_original' => fake()->word().'.pdf',
            'mime' => 'application/pdf',
            'taille' => fake()->numberBetween(1000, 4_000_000),
        ];
    }
}
