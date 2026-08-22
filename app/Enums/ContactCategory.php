<?php

namespace App\Enums;

/**
 * Catégorie d'un message de contact (Module 16) — permet à l'équipe
 * d'orienter/filtrer les messages reçus via le formulaire public.
 */
enum ContactCategory: string
{
    case InformationGenerale = 'information_generale';
    case Partenariat = 'partenariat';
    case Investissement = 'investissement';
    case Diaspora = 'diaspora';
    case Projet = 'projet';
    case Autre = 'autre';
}
