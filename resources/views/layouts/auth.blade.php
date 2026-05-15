<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Вход') — {{ config('app.name') }}</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #0f172a; }
        .card { background: #fff; padding: 2rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgb(0 0 0 / 0.08); width: 100%; max-width: 22rem; }
        label { display: block; font-size: 0.875rem; margin-bottom: 0.25rem; color: #475569; }
        input[type="email"], input[type="password"] { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #cbd5e1; border-radius: 0.375rem; box-sizing: border-box; }
        .field { margin-bottom: 1rem; }
        .error { color: #dc2626; font-size: 0.875rem; margin-top: 0.25rem; }
        button[type="submit"] { width: 100%; padding: 0.6rem; background: #4f46e5; color: #fff; border: none; border-radius: 0.375rem; font-weight: 600; cursor: pointer; }
        button[type="submit"]:hover { background: #4338ca; }
        .remember { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
        h1 { margin: 0 0 1.25rem; font-size: 1.25rem; }
        a { color: #4f46e5; }
    </style>
</head>
<body>
    <div class="card">
        @yield('content')
    </div>
</body>
</html>
