<?php

namespace App\Models;

use App\Enums\ContactCategory;
use App\Enums\ContactMessageStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Message de contact (Module 16). Volontairement PAS de HasMedia : aucune
 * pièce jointe sur le formulaire de contact dans cette version.
 */
class ContactMessage extends Model
{
    use HasFactory;

    // Déclaré explicitement par précaution suite au bug de pluralisation
    // constaté sur Talent (voir Talent::$table).
    protected $table = 'contact_messages';

    protected $fillable = [
        'categorie',
        'nom',
        'email',
        'telephone',
        'sujet',
        'message',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'categorie' => ContactCategory::class,
            'statut' => ContactMessageStatus::class,
        ];
    }
}
