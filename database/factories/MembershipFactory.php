<?php

namespace Database\Factories;

use App\Models\Membership;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Utilisée pour les tests hors flux de soumission réel — génère un numéro
 * de dossier fictif plausible (le vrai flux passe TOUJOURS par
 * MembershipReferenceGenerator, jamais cette factory), même principe que
 * ApplicationFactory.
 *
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'numero_membre' => 'CI-'.date('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'nom_complet' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'telephone' => fake()->phoneNumber(),
            'profession' => fake()->optional()->jobTitle(),
            'region' => fake()->randomElement(['ziguinchor', 'sedhiou', 'kolda', 'dakar', 'diaspora']),
            'departement' => fake()->optional()->city(),
            'domaine_contribution' => fake()->optional()->randomElement([
                'pole_capital_humain',
                'pole_economie_agriculture_attractivite',
                'pole_culture_communication',
                'pole_support',
                'commission_scientifique',
                'coordination_regionale',
                'comite_des_sages',
            ]),
            'type_contribution' => fake()->optional()->randomElement(['membre_actif', 'benevole_ponctuel', 'expert_conseiller_technique']),
            'photo_path' => 'membership-photos/fake-photo.jpg',
            'engagement_moral' => true,
            'suggestions_competences' => fake()->optional()->sentence(),
            'statut' => 'en_attente_paiement',
            'source' => 'site',
        ];
    }

    public function validee(): static
    {
        return $this->state(fn () => [
            'statut' => 'validee',
            'validated_at' => now(),
        ]);
    }

    public function manuelle(): static
    {
        return $this->state(fn () => [
            'source' => 'manuel',
            'statut' => 'validee',
            'validated_at' => now(),
        ]);
    }
}
