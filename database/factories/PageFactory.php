<?php

namespace Database\Factories;

use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $titre = fake()->unique()->sentence(3);

        return [
            'titre' => $titre,
            'slug' => Str::slug($titre).'-'.fake()->unique()->numberBetween(1, 100000),
            'corps' => fake()->paragraphs(3, true),
            'meta_description' => fake()->optional()->sentence(),
            'statut' => 'brouillon',
        ];
    }
}
