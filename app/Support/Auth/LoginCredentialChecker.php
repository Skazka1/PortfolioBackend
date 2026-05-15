<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Разделяет ошибки: неизвестный email, недоступный аккаунт, неверный пароль.
 */
final class LoginCredentialChecker
{
    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public static function validateAndResolveUser(array $credentials): User
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => [__('auth.email_not_found')],
            ]);
        }

        if (! $user->canLogin()) {
            throw ValidationException::withMessages([
                'email' => [__('auth.account_unavailable')],
            ]);
        }

        if (! Hash::check($credentials['password'], $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'password' => [__('auth.password_incorrect')],
            ]);
        }

        return $user;
    }
}
