<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && ($this->user()->isTeacher() || $this->user()->isAdmin());
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'date_time' => ['sometimes', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'genres' => ['sometimes', 'nullable', 'array'],
            'genres.*' => ['string', 'max:64', Rule::in(config('portfolio.event_genres', []))],
        ];
    }
}
