<?php

namespace Database\Factories;

use App\Models\Talent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Talent>
 */
class TalentFactory extends Factory
{
    protected $model = Talent::class;

    /**
     * Prénoms/noms et contexte volontairement ancrés en Casamance (Ziguinchor,
     * Kolda, Sédhiou) plutôt que du faker générique — cohérent avec le
     * public réel de Casa Impact.
     */
    private const PRENOMS = [
        'Aïssatou', 'Moussa', 'Fatou', 'Ousmane', 'Awa', 'Ibrahima', 'Coumba',
        'Mamadou', 'Bineta', 'Alassane', 'Khady', 'Cheikh', 'Ndeye', 'Lamine',
    ];

    private const NOMS = [
        'Diatta', 'Sané', 'Diémé', 'Badji', 'Coly', 'Manga', 'Diedhiou',
        'Sagna', 'Bassène', 'Camara', 'Diallo', 'Balde',
    ];

    private const PROJETS = [
        'un atelier de couture solidaire à Ziguinchor',
        'une coopérative maraîchère à Kolda',
        'un centre de formation en informatique à Sédhiou',
        'une unité de transformation de fruits locaux',
        'un projet d\'écotourisme communautaire en Casamance',
        'une radio communautaire de sensibilisation jeunesse',
    ];

    public function definition(): array
    {
        $nom = fake()->randomElement(self::PRENOMS).' '.fake()->randomElement(self::NOMS);

        return [
            'nom' => $nom,
            'slug' => Str::slug($nom).'-'.fake()->unique()->numberBetween(1, 100000),
            'domain_id' => null,
            'region' => fake()->randomElement(['ziguinchor', 'kolda', 'sedhiou']),
            'presentation' => fake()->paragraph(),
            'parcours' => fake()->paragraphs(2, true),
            'projet' => 'Porteur·euse de '.fake()->randomElement(self::PROJETS).'.',
            'realisations' => fake()->optional()->paragraph(),
            'temoignage' => fake()->optional()->paragraph(),
            'recit_titre' => fake()->optional()->sentence(6),
            'recit_corps' => fake()->optional()->paragraphs(3, true),
            'liens_externes' => fake()->optional()->randomElements([
                'https://facebook.com/'.Str::slug($nom),
                'https://instagram.com/'.Str::slug($nom),
            ], fake()->numberBetween(0, 2)),
            'statut' => 'brouillon',
        ];
    }
}
