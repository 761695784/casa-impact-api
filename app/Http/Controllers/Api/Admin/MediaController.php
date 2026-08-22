<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\ApplicationCall;
use App\Models\Media;
use App\Models\News;
use App\Models\Partner;
use App\Models\Program;
use App\Models\Talent;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Storage;

/**
 * Contrôleur générique et unique pour toute la Médiathèque (Module 12) —
 * pas un contrôleur par entité consommatrice, cohérent avec l'esprit
 * "table polymorphique unique" d'architecturev1.md §H. Autorisation via une
 * permission transversale `media.manage` plutôt qu'une permission par type
 * (ex. `programs.update`) : simplifie le modèle de droits pour une action
 * qui, dans les faits, est toujours faite par la même personne
 * (`communication`) quel que soit le type de contenu illustré.
 */
#[Group('Médiathèque — Admin')]
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

    public function store(StoreMediaRequest $request)
    {
        $this->authorize('media.manage');

        $data = $request->validated();
        $modelClass = self::MEDIABLE_MAP[$data['mediable_type']];
        $mediable = $modelClass::query()->findOrFail($data['mediable_id']);

        $file = $request->file('fichier');
        $chemin = $file->store('media/'.$data['mediable_type'], 'public');

        $media = Media::create([
            'mediable_type' => $mediable->getMorphClass(),
            'mediable_id' => $mediable->id,
            'collection' => $data['collection'] ?? 'default',
            'fichier' => $chemin,
            'nom_original' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'taille' => $file->getSize(),
            'legende' => $data['legende'] ?? null,
            'ordre' => $data['ordre'] ?? 0,
        ]);

        return (new MediaResource($media))
            ->additional(['message' => 'Média ajouté avec succès.'])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Media $media)
    {
        $this->authorize('media.manage');

        Storage::disk('public')->delete($media->fichier);
        $media->delete();

        return response()->json(['message' => 'Média supprimé avec succès.']);
    }
}
