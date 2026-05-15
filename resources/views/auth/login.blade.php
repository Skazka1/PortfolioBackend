@extends('layouts.auth')

@section('title', 'Вход')

@section('content')
    <h1>Вход в систему</h1>
    <form method="post" action="{{ url('/login') }}">
        @csrf
        <div class="field">
            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <div class="field">
            <label for="password">Пароль</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
            @error('password')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <label class="remember">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            Запомнить меня
        </label>
        <button type="submit">Войти</button>
    </form>
    <p style="margin-top:1rem;font-size:0.875rem;text-align:center;">
        <a href="{{ url('/') }}">На главную</a>
    </p>
@endsection
