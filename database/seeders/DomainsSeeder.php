<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;

/**
 * Les 6 domaines d'intervention proviennent du brief métier officiel
 * (section "Nos domaines"). `nom`, `slug`, `icone` et `description` sont
 * désormais tous renseignés dès le seed.
 *
 * Les textes de `description` ne sont PAS un texte institutionnel inventé
 * à part : ils sont composés à partir des 4 "axes stratégiques" déjà
 * définis pour chaque domaine dans `domainDetailsMetadata` côté frontend
 * (`lib/domain-visuals.tsx`) — mêmes intitulés, mêmes formulations. C'est
 * ce contenu-là (déjà validé et affiché sur la page de détail de chaque
 * domaine) qui sert de source, reformulé en un paragraphe continu pour le
 * champ `description` consommé par la page publique et par l'admin.
 *
 * `description` reste normalement modifiable ensuite depuis l'admin
 * (`DomaineEditDialog`), mais le seed n'affiche plus de bloc vide par
 * défaut.
 *
 * IMPORTANT — correspondance des slugs :
 * Le slug de chaque domaine est fixé explicitement ci-dessous (et non plus
 * dérivé de `nom` via `Str::slug()`). L'ancienne version du seeder générait
 * le slug à partir du nom (ex. "Jeunesse & Leadership" → "jeunesse-leadership"),
 * ce qui ne correspondait à AUCUNE des clés attendues côté frontend dans
 * `lib/domain-visuals.tsx` (ex. "jeunesse-et-leadership"). Résultat : la
 * page de détail de chaque domaine retombait systématiquement sur l'icône,
 * l'image et les 4 axes stratégiques par défaut de `getDomainMetadata()`,
 * au lieu des visuels et contenus spécifiquement conçus pour ce domaine.
 * Les slugs ci-dessous sont donc alignés caractère pour caractère sur les
 * clés de `domainVisuals` / `domainDetailsMetadata` côté frontend.
 *
 * `slug` reste volontairement absent de `Domain::$fillable` (voir le
 * modèle) : on utilise `firstOrNew()` + `forceFill()` plutôt que
 * `updateOrCreate()` pour pouvoir fixer ce champ nous-mêmes depuis ce
 * seeder de confiance, sans dépendre de l'assignation de masse ni risquer
 * qu'il soit silencieusement ignoré à la création.
 *
 * Rejouable sans dupliquer les 6 domaines (`firstOrNew` sur le slug).
 */
class DomainsSeeder extends Seeder
{
    public function run(): void
    {
        $domaines = [
            [
                'slug' => 'jeunesse-et-leadership',
                'nom' => 'Jeunesse & Leadership',
                'icone' => 'users',
                'description' => "Ce domaine s'articule autour de quatre axes. L'Académie du Leadership propose des formations certifiantes au management de projets, à la prise de parole en public et à la gouvernance locale. La Citoyenneté & Engagement organise l'immersion communautaire, des chantiers d'utilité publique et des consultations citoyennes dans les terroirs. Le Mentorat Intergénérationnel assure le parrainage des jeunes talents par des cadres, doyens et personnalités inspirantes de Casamance. Les Pôles Jeunesse Territoriaux animent des tiers-lieux et espaces de rencontre pour la jeunesse à Ziguinchor, Sédhiou et Kolda.",
            ],
            [
                'slug' => 'entrepreneuriat-et-innovation',
                'nom' => 'Entrepreneuriat & Innovation',
                'icone' => 'rocket',
                'description' => "Ce domaine repose sur quatre axes. L'Incubateur de Startups & PME assure un accompagnement méthodologique de l'idéation au prototypage et au déploiement commercial. Le Numérique, l'IA & la Cybersécurité proposent des formations accélérées aux métiers du code, du cloud, des données et de l'intelligence artificielle. L'Agro-Business & la Valeur Ajoutée portent la transformation moderne des filières fruitières (mangue, cajou, agrumes), rizicoles et maraîchères locales. L'Accès au Micro-Financement met en relation avec des fonds d'amorçage, business angels et coopératives de crédit.",
            ],
            [
                'slug' => 'culture-et-patrimoine',
                'nom' => 'Culture & Patrimoine',
                'icone' => 'landmark',
                'description' => "Ce domaine s'organise autour de quatre axes. La Valorisation du Patrimoine Vivant assure la documentation et la préservation des traditions, architectures sacrées et savoir-faire ancestraux. Les Industries Culturelles & Créatives structurent et professionnalisent artistes, musiciens, plasticiens et artisans d'art. Les Festivals & Événements Territoriaux promeuvent les grands rendez-vous artistiques et culturels qui font rayonner la Casamance. La Transmission aux Nouvelles Générations passe par des ateliers scolaires et communautaires d'éveil aux contes, arts et langues locales.",
            ],
            [
                'slug' => 'sport-et-promotion-des-talents',
                'nom' => 'Sport & Promotion des Talents',
                'icone' => 'trophy',
                'description' => "Ce domaine se déploie autour de quatre axes. La Détection & les Bourses Sportives permettent de repérer les jeunes prodiges en football, athlétisme, basket-ball, sports de combat et nautiques. Le Sport comme Vecteur d'Inclusion s'appuie sur des programmes éducatifs et d'insertion professionnelle basés sur les valeurs de discipline et de respect. Les Infrastructures Sportives de Proximité relèvent du plaidoyer et de l'aménagement de terrains multisports sécurisés pour les quartiers et villages. Le Rayonnement International des Athlètes passe par un accompagnement de carrière et une mise en relation avec des académies d'élite et fédérations.",
            ],
            [
                'slug' => 'tourisme-et-attractivite-territoriale',
                'nom' => 'Tourisme & Attractivité Territoriale',
                'icone' => 'palm-tree',
                'description' => "Ce domaine repose sur quatre axes. L'Écotourisme Communautaire développe des campements villageois éco-responsables et valorise les îles du fleuve. Les Circuits Découverte & Terroirs créent des routes touristiques authentiques reliant les richesses naturelles de Ziguinchor, Sédhiou et Kolda. Le Marketing & l'Image Territoriale déploient des campagnes de valorisation de la Casamance comme destination sûre, chaleureuse et captivante. La Formation aux Métiers de l'Accueil renforce les capacités en hôtellerie, guidage touristique, restauration et hygiène.",
            ],
            [
                'slug' => 'investissement-et-diaspora',
                'nom' => 'Investissement & Diaspora',
                'icone' => 'hand-coins',
                'description' => "Ce domaine s'articule autour de quatre axes. Le Guichet Diaspora & Investisseurs offre un accompagnement sur-mesure pour faciliter l'investissement productif de la diaspora en Casamance. Les Forums Économiques & B2B organisent des rencontres d'affaires entre porteurs de projets locaux et bailleurs internationaux. La Mobilisation des Compétences repose sur des missions de volontariat d'experts de la diaspora pour des interventions ciblées dans les universités et PME. Le Fonds d'Impact Territorial crée des instruments financiers innovants pour canaliser l'épargne vers des projets à fort impact social.",
            ],
        ];

        foreach ($domaines as $ordre => $data) {
            $domain = Domain::query()->firstOrNew(['slug' => $data['slug']]);

            $domain->forceFill([
                'nom' => $data['nom'],
                'description' => $data['description'],
                'icone' => $data['icone'],
                'ordre' => $ordre + 1,
                'statut' => 'actif',
            ])->save();
        }
    }
}
