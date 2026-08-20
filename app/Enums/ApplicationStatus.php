<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Nouvelle = 'nouvelle';
    case EnCoursEtude = 'en_cours_etude';
    case Preselectionnee = 'preselectionnee';
    case Retenue = 'retenue';
    case NonRetenue = 'non_retenue';

    // Décision validée le 2026-08-20 (gestion du dépassement de
    // nombre_places) : les soumissions au-delà du quota sont acceptées mais
    // marquées en_liste_attente plutôt que rejetées — voir
    // ApplicationCapacityChecker.
    case EnListeAttente = 'en_liste_attente';
}
