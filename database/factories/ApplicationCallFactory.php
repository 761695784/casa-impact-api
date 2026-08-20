<?php

namespace Database\Factories;

use App\Models\ApplicationCall;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApplicationCall>
 */
class ApplicationCallFactory extends Factory
{
    protected $model = ApplicationCall::class;

    public function definition(): array
    {
        $titre = fake()->unique()->sentence(4);
        $dateDebut = fake()->dateTimeBetween('+1 month', '+6 months');

        return [
            'titre' => $titre,
            'slug' => Str::slug($titre).'-'.fake()->unique()->numberBetween(1, 100000),
            'description' => fake()->paragraphs(2, true),
            'objectifs' => fake()->optional()->paragraph(),
            'public_cible' => fake()->optional()->sentence(),
            'region' => fake()->randomElement(['ziguinchor', 'kolda', 'sedhiou']),
            'lieu' => fake()->optional()->city(),
            'date_debut' => $dateDebut,
            'date_fin' => fake()->optional()->dateTimeBetween($dateDebut, '+1 year'),
            'date_limite' => fake()->dateTimeBetween('-1 month', $dateDebut),
            'duree' => fake()->optional()->randomElement(['3 jours', '1 semaine', '6 mois']),
            'nombre_places' => fake()->optional()->numberBetween(10, 50),
            'conditions' => fake()->optional()->paragraph(),
            // Volontairement PAS aléatoire (contrairement aux autres champs
            // optionnels ci-dessus) : un `documents_requis` généré au
            // hasard ferait échouer de façon imprévisible tout test qui
            // soumet une candidature sans préciser ce champ explicitement
            // (voir ApplicationSubmissionTest, Module 5, bug constaté le
            // 2026-08-20 — StoreApplicationRequest exige alors des
            // documents que le test ne fournit pas). Les tests qui veulent
            // vérifier cette règle fixent `documents_requis` eux-mêmes.
            'documents_requis' => null,
            'statut' => 'brouillon',
            'program_id' => Program::factory(),
        ];
    }
}
