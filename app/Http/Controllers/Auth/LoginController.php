<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Auth\LoginCredentialChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Форма входа (GET). */
    public function login(Request $request): View
    {
        return view('auth.login');
    }

    /** Проверка учётных данных и вход (POST). */
    public function authenticate(LoginRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = LoginCredentialChecker::validateAndResolveUser([
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    /** Выход из сессии (POST). */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
