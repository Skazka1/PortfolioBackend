<?php

namespace App\Http\Requests\Profile;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, Rule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'course' => ['nullable', 'string', 'max:32'],
            'group' => ['nullable', 'string', 'max:32'],
            'bio' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
