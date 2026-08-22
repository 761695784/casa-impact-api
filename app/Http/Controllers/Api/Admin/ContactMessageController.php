<?php

namespace App\Http\Controllers\Api\Admin;

use Dedoc\Scramble\Attributes\Group;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateContactMessageRequest;
use App\Http\Resources\Admin\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

/**
 * Pas de store() : les messages de contact ne sont créés que via la
 * soumission publique (voir Public\ContactMessageController) — voir
 * ContactMessagePolicy (pas de méthode create()).
 */
#[Group('Messages de contact — Admin')]
class ContactMessageController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $this->authorize('viewAny', ContactMessage::class);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $messages = ContactMessage::query()
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie', $request->string('categorie')))
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return ContactMessageResource::collection($messages);
    }

    public function show(ContactMessage $contactMessage)
    {
        $this->authorize('view', $contactMessage);

        return new ContactMessageResource($contactMessage);
    }

    /**
     * Seul le statut est modifiable (voir UpdateContactMessageRequest) — le
     * contenu saisi par l'expéditeur n'est jamais altéré par l'admin.
     */
    public function update(UpdateContactMessageRequest $request, ContactMessage $contactMessage)
    {
        $this->authorize('update', $contactMessage);

        $contactMessage->update($request->validated());

        return (new ContactMessageResource($contactMessage->fresh()))
            ->additional(['message' => 'Message marqué comme traité avec succès.']);
    }

    public function destroy(ContactMessage $contactMessage)
    {
        $this->authorize('delete', $contactMessage);

        $contactMessage->delete();

        return response()->json(['message' => 'Message supprimé avec succès.']);
    }

    /**
     * Export CSV — même filtrage que index(). Contient des données
     * personnelles (nom, email, téléphone) : endpoint admin-only, protégé
     * par la même permission que la liste (`contact-messages.view`).
     */
    public function export(Request $request)
    {
        $this->authorize('viewAny', ContactMessage::class);

        $messages = ContactMessage::query()
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('categorie'), fn ($q) => $q->where('categorie', $request->string('categorie')))
            ->orderByDesc('created_at')
            ->cursor();

        return $this->streamCsv(
            $messages,
            ['Catégorie', 'Nom', 'Email', 'Téléphone', 'Sujet', 'Message', 'Statut', 'Reçu le'],
            fn (ContactMessage $message) => [
                $message->categorie->value,
                $message->nom,
                $message->email,
                $message->telephone,
                $message->sujet,
                $message->message,
                $message->statut->value,
                $message->created_at->toDateString(),
            ],
            'messages-contact'
        );
    }
}
