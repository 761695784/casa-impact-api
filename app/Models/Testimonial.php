<?php

namespace App\Models;

use App\Enums\TestimonialStatus;
use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Volontairement PAS de HasLocation : un témoignage n'est pas géolocalisé
 * (contrairement à Program/ApplicationCall/Talent) — voir brief Module 10.
 * Volontairement PAS de generateUniqueSlug() : pas de colonne `slug` sur
 * cette table (pas de page de détail dédiée aux témoignages).
 */
class Testimonial extends Model
{
    use HasFactory;
    use HasMedia;

    // Déclaré explicitement par précaution suite au bug de pluralisation
    // constaté sur Talent (voir Talent::$table) — même si cette table
    // n'a pas montré le même symptôme, autant lever toute ambiguïté.
    protected $table = 'testimonials';

    protected $fillable = [
        'auteur',
        'role_organisation',
        'citation',
        'contexte',
        'program_id',
        'application_call_id',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'statut' => TestimonialStatus::class,
        ];
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function applicationCall()
    {
        return $this->belongsTo(ApplicationCall::class);
    }

    public function scopePublished($query)
    {
        return $query->where('statut', TestimonialStatus::Publie->value);
    }
}
