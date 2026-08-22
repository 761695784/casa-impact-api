<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    private const CITATIONS = [
        "Grâce à ce programme, j'ai pu ouvrir mon propre atelier à Ziguinchor et embaucher trois jeunes de mon quartier.",
        "L'accompagnement de Casa Impact m'a redonné confiance pour reprendre mes études après trois ans d'arrêt.",
        "Ce sont ces formations qui m'ont permis de structurer mon projet et d'obtenir mon premier financement.",
        "Aujourd'hui je forme à mon tour d'autres jeunes de Kolda grâce à ce que j'ai appris ici.",
        "Sans cet appel à candidatures, je n'aurais jamais osé présenter mon projet devant un jury.",
    ];

    private const ROLES = [
        "Bénéficiaire du programme", 'Partenaire local', 'Ancien participant',
        'Coordinatrice associative', 'Formateur communautaire',
    ];

    public function definition(): array
    {
        return [
            'auteur' => fake()->name(),
            'role_organisation' => fake()->optional()->randomElement(self::ROLES),
            'citation' => fake()->randomElement(self::CITATIONS),
            'contexte' => fake()->optional()->sentence(12),
            'program_id' => null,
            'application_call_id' => null,
            'statut' => 'brouillon',
        ];
    }
}
