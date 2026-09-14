<?php

namespace App\Services\Cartography;

/**
 * Table de correspondance "nom de commune/département en Casamance" -> coordonnées
 * GPS approximatives, utilisée pour placer sur la carte les Programmes, Appels à
 * candidatures et Talents — aucun de ces modèles n'a de latitude/longitude en base
 * (seulement `region` + un texte libre `localisation`/`lieu`), donc on déduit une
 * position à partir de ce texte plutôt que d'ajouter des champs partout (accord du
 * 2026-09-11).
 *
 * Les coordonnées ci-dessous sont des centres approximatifs (précision "commune",
 * pas "adresse") — largement suffisant pour une carte de visualisation territoriale.
 * Pour affiner ou ajouter une commune : ajoutez une entrée dans COMMUNES ci-dessous,
 * aucune autre modification n'est nécessaire.
 */
class CasamanceLocations
{
    /**
     * Centre approximatif de chacune des 3 régions — utilisé en repli quand la
     * commune/localisation saisie ne correspond à aucune entrée connue ci-dessous.
     */
    public const REGION_CENTROIDS = [
        'ziguinchor' => ['lat' => 12.5665, 'lng' => -16.2733, 'departement' => 'Ziguinchor', 'commune' => 'Ziguinchor'],
        'kolda' => ['lat' => 12.8833, 'lng' => -14.9500, 'departement' => 'Kolda', 'commune' => 'Kolda'],
        'sedhiou' => ['lat' => 12.7081, 'lng' => -15.5569, 'departement' => 'Sédhiou', 'commune' => 'Sédhiou'],
    ];

    /**
     * Communes/localités connues de la zone d'intervention (clé = nom normalisé,
     * voir normalize()), avec leur département de rattachement.
     */
    public const COMMUNES = [
        // Région de Ziguinchor (départements Ziguinchor, Bignona, Oussouye)
        'ziguinchor' => ['lat' => 12.5665, 'lng' => -16.2733, 'departement' => 'Ziguinchor'],
        'bignona' => ['lat' => 12.8103, 'lng' => -16.2261, 'departement' => 'Bignona'],
        'oussouye' => ['lat' => 12.4850, 'lng' => -16.5486, 'departement' => 'Oussouye'],
        'thionck essyl' => ['lat' => 12.7333, 'lng' => -16.3500, 'departement' => 'Bignona'],
        'diouloulou' => ['lat' => 13.0500, 'lng' => -16.5833, 'departement' => 'Bignona'],
        'kafountine' => ['lat' => 12.9333, 'lng' => -16.7500, 'departement' => 'Bignona'],
        'cap skirring' => ['lat' => 12.4000, 'lng' => -16.7500, 'departement' => 'Oussouye'],
        'nyassia' => ['lat' => 12.5500, 'lng' => -16.4500, 'departement' => 'Ziguinchor'],
        'niaguis' => ['lat' => 12.4667, 'lng' => -16.2167, 'departement' => 'Ziguinchor'],
        'adeane' => ['lat' => 12.4500, 'lng' => -16.1500, 'departement' => 'Ziguinchor'],

        // Région de Kolda (départements Kolda, Vélingara, Médina Yoro Foulah)
        'kolda' => ['lat' => 12.8833, 'lng' => -14.9500, 'departement' => 'Kolda'],
        'velingara' => ['lat' => 13.1500, 'lng' => -14.1167, 'departement' => 'Vélingara'],
        'medina yoro foulah' => ['lat' => 13.0500, 'lng' => -14.6667, 'departement' => 'Médina Yoro Foulah'],
        'pata' => ['lat' => 12.8500, 'lng' => -14.7500, 'departement' => 'Vélingara'],
        'dabo' => ['lat' => 12.7667, 'lng' => -14.3500, 'departement' => 'Kolda'],
        'salikegne' => ['lat' => 12.9000, 'lng' => -15.0500, 'departement' => 'Kolda'],

        // Région de Sédhiou (départements Sédhiou, Bounkiling, Goudomp)
        'sedhiou' => ['lat' => 12.7081, 'lng' => -15.5569, 'departement' => 'Sédhiou'],
        'bounkiling' => ['lat' => 12.8500, 'lng' => -15.6833, 'departement' => 'Bounkiling'],
        'goudomp' => ['lat' => 12.5167, 'lng' => -15.8833, 'departement' => 'Goudomp'],
        'marsassoum' => ['lat' => 12.8167, 'lng' => -15.9333, 'departement' => 'Bounkiling'],
        'diattacounda' => ['lat' => 12.6500, 'lng' => -15.7500, 'departement' => 'Goudomp'],
    ];

    /**
     * Résout une position approximative pour un point à partir de sa région et
     * d'un texte libre de localisation (commune, ville, quartier...). Retourne
     * toujours ['lat', 'lng', 'departement', 'commune'].
     *
     * Si la localisation ne correspond à aucune commune connue, on retombe sur le
     * centre de la région, légèrement décalé (jitter déterministe basé sur $seed)
     * pour éviter que plusieurs points sans commune reconnue ne se superposent
     * exactement sur la carte.
     */
    public static function resolve(?string $region, ?string $localisation, int $seed = 0): array
    {
        $region = $region ? strtolower(trim($region)) : null;
        $key = self::normalize($localisation);

        if ($key !== null) {
            foreach (self::COMMUNES as $commune => $coords) {
                if ($key === $commune || str_contains($key, $commune) || str_contains($commune, $key)) {
                    return [
                        'lat' => $coords['lat'],
                        'lng' => $coords['lng'],
                        'departement' => $coords['departement'],
                        'commune' => self::titleCase($localisation),
                    ];
                }
            }
        }

        $centroid = self::REGION_CENTROIDS[$region] ?? null;

        if (! $centroid) {
            // Région inconnue/absente : centre par défaut sur Ziguinchor (siège).
            $centroid = self::REGION_CENTROIDS['ziguinchor'];
        }

        [$latJitter, $lngJitter] = self::jitter($seed);

        return [
            'lat' => round($centroid['lat'] + $latJitter, 6),
            'lng' => round($centroid['lng'] + $lngJitter, 6),
            'departement' => $centroid['departement'],
            'commune' => $localisation ? self::titleCase($localisation) : $centroid['commune'],
        ];
    }

    /**
     * Minuscule, sans accents, sans tirets ni espaces multiples — pour comparer
     * une localisation saisie librement aux clés de COMMUNES.
     */
    private static function normalize(?string $value): ?string
    {
        if (! $value || trim($value) === '') {
            return null;
        }

        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
            '-' => ' ',
        ]);
        $value = preg_replace('/\b(commune de|departement de|departement du|ville de|region de)\b/', '', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim($value) ?: null;
    }

    private static function titleCase(string $value): string
    {
        return mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Décalage déterministe en degrés (~ jusqu'à 500m) dérivé de $seed, pour
     * répartir visuellement les points qui retombent sur le même centroïde de
     * région plutôt que de tous les empiler exactement au même endroit.
     */
    private static function jitter(int $seed): array
    {
        $angle = ($seed * 47) % 360;
        $radius = 0.01 + (($seed * 13) % 5) * 0.008; // ~1 à 5 km

        return [
            $radius * cos(deg2rad($angle)),
            $radius * sin(deg2rad($angle)),
        ];
    }
}
