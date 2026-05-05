<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:65535'],
            'github_url' => ['nullable', 'string', 'max:500'],
            'technologies' => ['nullable', 'array'],
            'technologies.*' => ['string', 'max:64'],
            'is_published' => ['sometimes', 'boolean'],
            'collaborator_ids' => ['nullable', 'array'],
            'collaborator_ids.*' => [Rule::exists('users', 'id')],
        ];
    }
}
