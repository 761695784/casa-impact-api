<?php

namespace App\Services;

use App\Enums\MembershipRegion;
use App\Models\Membership;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Génère la carte de membre PDF à partir du gabarit fourni par
 * l'utilisateur le 2026-08-24 (`ModeleCarteMembre.pdf` — bandeau vert
 * foncé "Carte de membre" en rotation, logo, pastille {{ID}}, encadré
 * photo doré, {{NOM}}/{{STATUT}}/{{REGION}}/{{DATE}}, filigrane baobab).
 * Reproduit via `resources/views/pdf/membership-card.blade.php` +
 * DomPDF (barryvdh/laravel-dompdf) — choisi explicitement car pur PHP,
 * sans binaire externe (Chromium/wkhtmltopdf), donc sans risque
 * d'installation supplémentaire sur l'environnement Windows de
 * l'utilisateur. Voir README de cette livraison : `composer require
 * barryvdh/laravel-dompdf` est nécessaire, le package n'est pas encore
 * dans le projet.
 */
class MembershipCardService
{
    public function generate(Membership $membership): string
    {
        $pdf = Pdf::loadView('pdf.membership-card', [
            'id' => $membership->numero_membre,
            'nom' => $membership->nom_complet,
            'statut' => $this->statutLabel($membership),
            'region' => $this->regionLabel($membership->region),
            'date' => ($membership->validated_at ?? now())->format('d/m/Y'),
            'photoAbsolutePath' => $membership->photo_path
                ? storage_path('app/public/'.$membership->photo_path)
                : null,
        ]);

        // Taille personnalisée proche du gabarit fourni (format carte
        // large, ratio ~16:9), en points — évite les marges blanches
        // d'un A4 qui n'a aucun sens pour une carte de membre.
        $pdf->setPaper([0, 0, 680, 383]);

        return $pdf->output();
    }

    /**
     * Le champ {{STATUT}} du gabarit sert de "titre" affiché sous le nom
     * (ex. "Membre actif") — décision de conception du 2026-08-24 : le
     * statut de VALIDATION (`en_attente_paiement`/`validee`/`refusee`,
     * MembershipStatus) n'a aucun intérêt à figurer sur la carte, puisque
     * la carte n'est justement générée QUE lorsque l'adhésion est déjà
     * validée — ce serait toujours la même valeur. Le champ
     * `type_contribution` (Membre actif / Bénévole ponctuel / Expert -
     * Conseiller technique) est en revanche pertinent et variable, d'où
     * son utilisation ici. Champ optionnel au formulaire : "Membre" sert
     * de repli si l'adhérent ne l'a pas renseigné.
     */
    private function statutLabel(Membership $membership): string
    {
        return match ($membership->type_contribution) {
            \App\Enums\ContributionType::MembreActif => 'Membre actif',
            \App\Enums\ContributionType::BenevolePonctuel => 'Bénévole ponctuel',
            \App\Enums\ContributionType::ExpertConseillerTechnique => 'Expert / Conseiller technique',
            default => 'Membre',
        };
    }

    private function regionLabel(?MembershipRegion $region): string
    {
        return match ($region) {
            MembershipRegion::Ziguinchor => 'Ziguinchor',
            MembershipRegion::Sedhiou => 'Sédhiou',
            MembershipRegion::Kolda => 'Kolda',
            MembershipRegion::Dakar => 'Dakar',
            MembershipRegion::Diaspora => 'Diaspora',
            default => '—',
        };
    }
}
