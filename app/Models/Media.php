<?php

namespace App\Models;

use App\Enums\MediaCategorie;
use App\Enums\MediaStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Médiathèque (module refondu le 2026-09-10) : `Media` est désormais un
 * fichier de bibliothèque autonome, réutilisable par plusieurs fiches à la
 * fois. L'usage par fiche (qui l'utilise, dans quelle collection, dans quel
 * ordre) vit dans `media_attachments` (voir MediaAttachment et
 * App\Traits\HasMedia::media(), qui expose la relation morphToMany aux
 * modèles consommateurs).
 */
class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'nom',
        'fichier',
        'nom_original',
        'mime',
        'taille',
        'alt',
        'legende',
        'categorie',
        'statut',
        'dimensions',
    ];

    protected function casts(): array
    {
        return [
            'taille' => 'integer',
            'categorie' => MediaCategorie::class,
            'statut' => MediaStatus::class,
        ];
    }

    /** Image ou document, dérivé du MIME plutôt que stocké (évite toute désynchronisation). */
    public function type(): string
    {
        return str_starts_with((string) $this->mime, 'image/') ? 'image' : 'document';
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MediaAttachment::class);
    }
}
