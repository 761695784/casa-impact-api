<?php

namespace App\Http\Controllers\Api\Public;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreMembershipRequest;
use App\Http\Resources\Public\MembershipConfirmationResource;
use App\Models\Membership;
use App\Notifications\MembershipReceived;
use App\Services\MembershipReferenceGenerator;
use Illuminate\Support\Facades\Notification;

/**
 * Aucune authentification requise. Seule action publique du module
 * Adhésion : la saisie manuelle (membres antérieurs au site) passe par
 * Admin\MembershipController::store() — voir Admin\MembershipPolicy.
 */
class MembershipController extends Controller
{
    public function __construct(private MembershipReferenceGenerator $referenceGenerator)
    {
    }

    public function store(StoreMembershipRequest $request)
    {
        $data = $request->validated();

        $photoPath = $request->file('photo')->store('membership-photos', 'public');

        $membership = Membership::create([
            ...collect($data)->except(['photo'])->all(),
            'numero_membre' => $this->referenceGenerator->generate(),
            'photo_path' => $photoPath,
            'statut' => MembershipStatus::EnAttentePaiement->value,
            'source' => 'site',
        ]);

        Notification::route('mail', $membership->email)->notify(new MembershipReceived($membership));

        return (new MembershipConfirmationResource($membership))
            ->additional(['message' => 'Demande d\'adhésion soumise avec succès. Consultez votre email pour les instructions de paiement.'])
            ->response()
            ->setStatusCode(201);
    }
}
