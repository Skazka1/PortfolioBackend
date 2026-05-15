<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'date_time' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'genres' => ['required', 'array', 'min:1'],
            'genres.*' => ['string', 'max:64', Rule::in(config('portfolio.event_genres', []))],
        ];
    }
}
