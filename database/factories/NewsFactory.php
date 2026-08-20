<?php

namespace Database\Factories;

use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        $titre = fake()->unique()->sentence(4);

        return [
            'titre' => $titre,
            'slug' => Str::slug($titre).'-'.fake()->unique()->numberBetween(1, 100000),
            'type' => 'article',
            'corps' => fake()->paragraphs(3, true),
            'statut' => 'brouillon',
        ];
    }
}
