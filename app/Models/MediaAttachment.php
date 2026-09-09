<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pivot polymorphique explicite (pas un simple tableau `pivot`) : sert de
 * table pour `HasMedia::media()` (morphToMany) ET reste manipulable
 * directement (MediaController::attach/detach) sans passer par la relation
 * du modèle consommateur — utile quand on ne connaît que
 * mediable_type/mediable_id (ex. le type venant de MediaController::MEDIABLE_MAP).
 */
class MediaAttachment extends Model
{
    protected $table = 'media_attachments';

    protected $fillable = [
        'media_id',
        'mediable_type',
        'mediable_id',
        'collection',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'ordre' => 'integer',
        ];
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }
}
