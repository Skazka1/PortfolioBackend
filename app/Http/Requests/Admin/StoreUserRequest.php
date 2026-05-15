<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in([UserRole::Teacher->value, UserRole::Student->value])],
            'course' => ['nullable', 'string', 'max:32', 'required_if:role,student'],
            'group' => ['nullable', 'string', 'max:32', 'required_if:role,student'],
            'password' => ['nullable', 'string', 'min:8'],
        ];
    }
}
