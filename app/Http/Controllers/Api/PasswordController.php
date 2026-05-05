<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));
        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => 'Ссылка для сброса пароля отправлена, если email найден.']);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $status = Password::reset(
            $data,
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => 'Пароль обновлён.']);
    }
}
