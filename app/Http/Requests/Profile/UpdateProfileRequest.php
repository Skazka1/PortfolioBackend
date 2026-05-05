<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:32'],
            'group' => ['nullable', 'string', 'max:32'],
            'year_of_graduation' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'bio' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
