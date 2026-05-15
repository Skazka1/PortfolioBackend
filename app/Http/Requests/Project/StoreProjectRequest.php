<?php

namespace App\Http\Requests\Project;

use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('create', Project::class);
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65535'],
            'github_url' => [
                'nullable', 'string', 'max:500',
            ],
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
            /** Только прошедшие события из календаря. */
            'campus_event_id' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where(fn ($q) => $q->where('date_time', '<', now())),
            ],
            'preview_image' => [
                'nullable',
                File::image()
                    ->max(5120)
                    ->dimensions(
                        Rule::dimensions()->maxWidth(8000)->maxHeight(8000)
                    ),
            ],
        ];
    }
}
