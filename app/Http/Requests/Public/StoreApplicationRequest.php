<?php

namespace App\Http\Requests\Public;

use App\Enums\Region;
use App\Models\ApplicationCall;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'application_call_id' => ['required', 'integer', 'exists:application_calls,id'],

            'nom' => ['required', 'string', 'max:255'],
            'prenom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],

            'region' => ['required', Rule::enum(Region::class)],
            'lieu' => ['nullable', 'string', 'max:255'],

            // Rendus obligatoires (décision de l'utilisateur, 2026-08-20 —
            // à l'origine nullable dans ma proposition initiale).
            'tranche_age' => ['required', 'string', 'max:50'],
            'niveau_etudes' => ['required', 'string', 'max:255'],
            'situation_professionnelle' => ['nullable', 'string', 'max:255'],
            'competences' => ['nullable', 'string'],
            'experience' => ['nullable', 'string'],
            'motivation' => ['nullable', 'string'],
            'projet' => ['nullable', 'string'],

            // Fichiers envoyés en multipart, ex. documents[cv], 5 Mo max,
            // `mimes` vérifie le contenu réel du fichier (fileinfo), pas
            // seulement l'extension déclarée par le client.
            'documents' => ['nullable', 'array'],
            'documents.*' => ['file', 'max:5120', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }

    /**
     * Vérifie que tous les types de documents listés dans
     * `ApplicationCall.documents_requis` sont bien fournis — règle métier
     * propre à l'appel visé, pas exprimable comme une règle Laravel
     * statique puisqu'elle dépend d'une autre ressource.
     *
     * Depuis l'alignement du formulaire admin (chaque entrée est désormais
     * un objet {cle, libelle, requis, formats?, taille_max?} plutôt qu'une
     * simple chaîne), on lit `cle` et on ne bloque que les documents dont
     * `requis` vaut true — une ancienne entrée stockée en simple chaîne
     * (données antérieures à cet alignement) reste traitée comme
     * obligatoire, pour ne rien changer au comportement déjà en place.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $applicationCallId = $this->input('application_call_id');

            if (! $applicationCallId) {
                return;
            }

            $applicationCall = ApplicationCall::find($applicationCallId);
            $documentsRequis = $applicationCall?->documents_requis ?? [];

            foreach ($documentsRequis as $document) {
                if (is_array($document)) {
                    $cle = $document['cle'] ?? null;
                    $estRequis = $document['requis'] ?? true;
                } else {
                    $cle = $document;
                    $estRequis = true;
                }

                if (! $cle || ! $estRequis) {
                    continue;
                }

                if (! $this->hasFile("documents.{$cle}")) {
                    $validator->errors()->add(
                        "documents.{$cle}",
                        "Le document « {$cle} » est requis pour cet appel à candidatures."
                    );
                }
            }
        });
    }
}
