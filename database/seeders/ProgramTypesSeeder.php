<?php

namespace Database\Seeders;

use App\Models\ProgramType;
use Illuminate\Database\Seeder;

/**
 * Contrairement à DomainsSeeder, ProgramType n'est pas un référentiel
 * verrouillé (voir ProgramType::class) : cette liste sert de point de
 * départ éditorial cohérent avec les 4 modalités déjà annoncées dans
 * l'admin (app/admin/(protected)/types-de-programme/page.tsx : "Formation,
 * Accompagnement, Événements, Bourses"), pas une contrainte fermée —
 * l'admin peut en ajouter, modifier ou désactiver librement via le CRUD.
 *
 * Comme pour DomainsSeeder : `slug` n'est pas dans $fillable
 * (ProgramType::$fillable), donc firstOrNew()+forceFill() plutôt que
 * updateOrCreate() pour éviter que le slug soit silencieusement ignoré
 * à la création.
 */
class ProgramTypesSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug' => 'formation',
                'nom' => 'Formation',
                'description' => "Sessions structurées de renforcement de compétences (techniques, entrepreneuriales, culturelles ou territoriales), en présentiel ou hybride, sanctionnées par une attestation de participation.",
                'ordre' => 1,
            ],
            [
                'slug' => 'accompagnement',
                'nom' => 'Accompagnement',
                'description' => "Suivi individualisé ou en petit groupe sur la durée (mentorat, coaching de projet, appui-conseil), destiné à consolider une initiative déjà engagée par ses bénéficiaires.",
                'ordre' => 2,
            ],
            [
                'slug' => 'evenements',
                'nom' => 'Événements',
                'description' => "Temps forts ponctuels ouverts au public ou à un réseau ciblé (forums, rencontres, festivals, journées de mobilisation) qui valorisent les initiatives de la Casamance et créent du lien entre acteurs.",
                'ordre' => 3,
            ],
            [
                'slug' => 'bourses',
                'nom' => 'Bourses',
                'description' => "Soutien financier direct attribué sur dossier à des porteurs de projet ou étudiants, destiné à lever un obstacle matériel précis (études, lancement d'activité, participation à un événement qualifiant).",
                'ordre' => 4,
            ],
        ];

        foreach ($types as $data) {
            $type = ProgramType::query()->firstOrNew(['slug' => $data['slug']]);
            $type->forceFill([
                'slug' => $data['slug'],
                'nom' => $data['nom'],
                'description' => $data['description'],
                'statut' => 'actif',
                'ordre' => $data['ordre'],
            ])->save();
        }
    }
}
