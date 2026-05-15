<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Support\Auth\LoginCredentialChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request): UserResource
    {
        $data = $request->validated();
        $user = LoginCredentialChecker::validateAndResolveUser([
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->tokens()->delete();
        $plainTextToken = $user->createToken('api')->plainTextToken;

        return UserResource::make($user)->additional([
            'token' => $plainTextToken,
        ]);
    }

    public function user(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json(['ok' => true]);
    }
}
