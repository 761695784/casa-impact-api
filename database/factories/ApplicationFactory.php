<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\ApplicationCall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Utilisée pour les tests hors flux de soumission réel — génère une
 * référence fictive plausible (le vrai flux passe TOUJOURS par
 * ApplicationReferenceGenerator, jamais cette factory).
 *
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'reference' => 'CI-'.date('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'application_call_id' => ApplicationCall::factory(),
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'telephone' => fake()->optional()->phoneNumber(),
            'region' => fake()->randomElement(['ziguinchor', 'kolda', 'sedhiou']),
            'lieu' => fake()->optional()->city(),
            'tranche_age' => fake()->optional()->randomElement(['18-25', '26-35', '36-45']),
            'niveau_etudes' => fake()->optional()->randomElement(['secondaire', 'licence', 'master']),
            'situation_professionnelle' => fake()->optional()->randomElement(['etudiant', 'sans-emploi', 'entrepreneur']),
            'competences' => fake()->optional()->sentence(),
            'experience' => fake()->optional()->paragraph(),
            'motivation' => fake()->optional()->paragraph(),
            'projet' => fake()->optional()->paragraph(),
            'statut' => 'nouvelle',
        ];
    }
}
