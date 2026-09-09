<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachMediaRequest;
use App\Http\Requests\Admin\DetachMediaRequest;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\ApplicationCall;
use App\Models\Media;
use App\Models\MediaAttachment;
use App\Models\News;
use App\Models\Partner;
use App\Models\Program;
use App\Models\Talent;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Contrôleur générique et unique pour toute la Médiathèque (Module 12,
 * refondu le 2026-09-10 en vraie bibliothèque réutilisable — voir
 * App\Models\Media, App\Models\MediaAttachment, App\Traits\HasMedia).
 * Autorisation via une permission transversale `media.manage` plutôt qu'une
 * permission par type consommateur : simplifie le modèle de droits pour une
 * action qui, dans les faits, est toujours faite par la même personne
 * (`communication`) quel que soit le type de contenu illustré.
 */
class MediaController extends Controller
{
    /**
     * Alias public → classe réelle. Volontairement une liste blanche
     * explicite (pas de résolution dynamique `"App\\Models\\".ucfirst($type)`)
     * — empêche d'attacher un média à n'importe quel modèle Eloquent de
     * l'application via une requête forgée.
     */
    public const MEDIABLE_MAP = [
        'program' => Program::class,
        'application-call' => ApplicationCall::class,
        'news' => News::class,
        'talent' => Talent::class,
        'partner' => Partner::class,
        'testimonial' => Testimonial::class,
    ];

    /**
     * Bibliothèque complète (médiathèque) — indépendante de tout usage par
     * fiche. `type` (image/document) est dérivé du MIME, pas une colonne.
     */
    public function index(Request $request)
    {
        $this->authorize('media.manage');

        $media = Media::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('nom', 'like', $term)
                        ->orWhere('nom_original', 'like', $term)
                        ->orWhere('alt', 'like', $term)
                        ->orWhere('legende', 'like', $term);
                });
            })
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie', $request->string('categorie')))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->input('type') === 'image', fn ($q) => $q->where('mime', 'like', 'image/%'))
            ->when($request->input('type') === 'document', fn ($q) => $q->where('mime', 'not like', 'image/%'))
            ->orderByDesc('created_at')
            ->get();

        return MediaResource::collection($media);
    }

    public function show(Media $media)
    {
        $this->authorize('media.manage');

        return new MediaResource($media);
    }

    /**
     * Upload dans la bibliothèque. `mediable_type`/`mediable_id` restent
     * acceptés en option pour un attachement immédiat (raccourci pratique
     * quand on sait déjà à quelle fiche la photo est destinée) — sinon le
     * fichier alimente uniquement la médiathèque, à rattacher plus tard via
     * attach().
     */
    public function store(StoreMediaRequest $request)
    {
        $this->authorize('media.manage');

        $data = $request->validated();
        $file = $request->file('fichier');
        $isImage = str_starts_with($file->getMimeType(), 'image/');
        $chemin = $file->store('media/'.($isImage ? 'images' : 'documents'), 'public');

        $dimensions = null;
        if ($isImage) {
            $size = @getimagesize($file->getRealPath());
            if ($size) {
                $dimensions = "{$size[0]}x{$size[1]}";
            }
        }

        $media = Media::create([
            'nom' => $data['nom'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'fichier' => $chemin,
            'nom_original' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'taille' => $file->getSize(),
            'alt' => $data['alt'] ?? null,
            'legende' => $data['legende'] ?? null,
            'categorie' => $data['categorie'] ?? 'general',
            'statut' => 'actif',
            'dimensions' => $dimensions,
        ]);

        if (! empty($data['mediable_type'])) {
            $modelClass = self::MEDIABLE_MAP[$data['mediable_type']];
            $mediable = $modelClass::query()->findOrFail($data['mediable_id']);

            MediaAttachment::create([
                'media_id' => $media->id,
                'mediable_type' => $mediable->getMorphClass(),
                'mediable_id' => $mediable->id,
                'collection' => $data['collection'] ?? 'gallery',
                'ordre' => $data['ordre'] ?? 0,
            ]);
        }

        return (new MediaResource($media))
            ->additional(['message' => 'Média ajouté avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    /** Métadonnées de bibliothèque uniquement — jamais l'attachement (voir attach()/detach()). */
    public function update(UpdateMediaRequest $request, Media $media)
    {
        $this->authorize('media.manage');

        $media->update($request->validated());

        return (new MediaResource($media->fresh()))
            ->additional(['message' => 'Média mis à jour avec succès.']);
    }

    /**
     * Supprime le fichier de la bibliothèque — et donc de TOUTES les fiches
     * où il était utilisé (les lignes media_attachments sont supprimées en
     * cascade par la contrainte FK). Pour retirer une photo d'une seule
     * fiche sans la supprimer partout, utiliser detach().
     */
    public function destroy(Media $media)
    {
        $this->authorize('media.manage');

        Storage::disk('public')->delete($media->fichier);
        $media->delete();

        return response()->json(['message' => 'Média supprimé avec succès.']);
    }

    /** Rattache une photo déjà présente dans la bibliothèque à une fiche. */
    public function attach(AttachMediaRequest $request, Media $media)
    {
        $this->authorize('media.manage');

        $data = $request->validated();
        $modelClass = self::MEDIABLE_MAP[$data['mediable_type']];
        $mediable = $modelClass::query()->findOrFail($data['mediable_id']);

        $attachment = MediaAttachment::updateOrCreate(
            [
                'media_id' => $media->id,
                'mediable_type' => $mediable->getMorphClass(),
                'mediable_id' => $mediable->id,
                'collection' => $data['collection'] ?? 'gallery',
            ],
            [
                'ordre' => $data['ordre'] ?? 0,
            ]
        );

        return (new MediaResource($media))
            ->additional([
                'message' => 'Média rattaché avec succès.',
                'attachment_id' => $attachment->id,
            ]);
    }

    /** Retire une photo d'une fiche (la photo reste dans la bibliothèque). */
    public function detach(DetachMediaRequest $request, Media $media)
    {
        $this->authorize('media.manage');

        $data = $request->validated();
        $modelClass = self::MEDIABLE_MAP[$data['mediable_type']];
        $mediable = $modelClass::query()->findOrFail($data['mediable_id']);

        $deleted = $media->attachments()
            ->where('mediable_type', $mediable->getMorphClass())
            ->where('mediable_id', $mediable->id)
            ->when(! empty($data['collection']), fn ($q) => $q->where('collection', $data['collection']))
            ->delete();

        return response()->json([
            'message' => $deleted > 0
                ? 'Média détaché avec succès.'
                : "Cette photo n'était pas rattachée à cette fiche.",
        ]);
    }
}
