<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): UserResource|JsonResponse
    {
        $data = $request->validated();
        $user = User::query()->where('email', $data['email'])->first();
        if (! $user?->canLogin()) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }
        if (! Auth::guard('web')->attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $request->boolean('remember', false)
        )) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $request->session()->regenerate();
        $fresh = Auth::guard('web')->user();
        if (! $fresh) {
            throw ValidationException::withMessages(['email' => [__('auth.failed')]]);
        }

        return UserResource::make($fresh);
    }

    public function user(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }
}
