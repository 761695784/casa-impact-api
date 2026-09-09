<?php

namespace App\Http\Requests\Admin;

use App\Http\Controllers\Api\Admin\MediaController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mediable_type' => ['required', Rule::in(array_keys(MediaController::MEDIABLE_MAP))],
            'mediable_id' => ['required', 'integer'],
            'collection' => ['nullable', 'string', 'max:100'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
