<?php

namespace App\Http\Requests\Project;

use App\Enums\UserRole;
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
            'technologies.*' => ['string', 'max:64', Rule::in(config('portfolio.event_genres', []))],
            'is_published' => ['sometimes', 'boolean'],
            'collaborator_ids' => ['nullable', 'array'],
            'collaborator_ids.*' => [Rule::exists('users', 'id')],
            'supervisor_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('role', UserRole::Teacher->value)->where('is_active', true);
                }),
            ],
            'campus_event_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where(fn ($q) => $q->where('date_time', '<', now())),
            ],
        ];
    }
}
