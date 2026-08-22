<?php

namespace App\Models;

use App\Enums\PartnerStatus;
use App\Enums\PartnerType;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Partenaires (Module 11). Volontairement PAS de slug (pas de page de détail
 * individuelle dans cette version) et PAS de colonne `logo_url` : le logo est
 * géré exclusivement via la Médiathèque polymorphique, collection `'logo'`
 * (voir HasMedia::mediaInCollection()).
 *
 * Nom de classe exact requis par App\Http\Controllers\Api\Admin\MediaController::MEDIABLE_MAP.
 */
class Partner extends Model
{
    use HasFactory;
    use HasMedia;

    // Déclaré explicitement par précaution suite au bug de pluralisation
    // constaté sur Talent (voir Talent::$table).
    protected $table = 'partners';

    protected $fillable = [
        'nom',
        'description',
        'lien',
        'type',
        'statut',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'type' => PartnerType::class,
            'statut' => PartnerStatus::class,
            'ordre' => 'integer',
        ];
    }

    /**
     * "Actif/inactif", pas "publié/brouillon" — terminologie propre à ce
     * module, volontairement pas de scopePublished() ici.
     */
    public function scopeActive($query)
    {
        return $query->where('statut', PartnerStatus::Actif->value);
    }
}
