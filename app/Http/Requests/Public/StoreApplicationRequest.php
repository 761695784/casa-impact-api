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

            foreach ($documentsRequis as $type) {
                if (! $this->hasFile("documents.{$type}")) {
                    $validator->errors()->add(
                        "documents.{$type}",
                        "Le document « {$type} » est requis pour cet appel à candidatures."
                    );
                }
            }
        });
    }
}
