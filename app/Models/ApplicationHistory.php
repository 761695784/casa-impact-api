<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une ligne = un événement sur une candidature (changement de statut et/ou
 * email envoyé) — voir la migration create_application_history_table pour
 * le détail des deux `type` possibles. Alimentée uniquement par
 * Admin\ApplicationController (update()/notify()), jamais modifiable ni
 * supprimable depuis l'admin : c'est un journal, pas une ressource CRUD.
 */
class ApplicationHistory extends Model
{
    protected $table = 'application_history';

    protected $fillable = [
        'application_id',
        'type',
        'ancien_statut',
        'nouveau_statut',
        'sujet_email',
        'user_id',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
