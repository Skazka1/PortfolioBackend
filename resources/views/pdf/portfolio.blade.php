<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.4; }
        h1 { font-size: 18pt; margin-bottom: 4px; }
        .meta { color: #444; font-size: 10pt; margin-bottom: 16px; }
        h2 { font-size: 14pt; margin-top: 16px; border-bottom: 1px solid #ccc; }
        p { margin: 4px 0; }
    </style>
</head>
<body>
    <h1>{{ $user->name }}</h1>
    <div class="meta">
        @if($user->course) Курс: {{ $user->course }} @endif
        @if($user->group) | Группа: {{ $user->group }} @endif
    </div>
    @if($user->bio)
        <h2>Введение</h2>
        <p style="white-space: pre-wrap;">{{ $user->bio }}</p>
    @endif
    <h2>Проекты</h2>
    @foreach($projects as $p)
        <h2 style="border:none; margin-top: 12px;">{{ $p->title }}</h2>
        @if($p->supervisor)
            <p style="margin: 2px 0; font-size: 10pt; color: #444;"><strong>Научный руководитель:</strong> {{ $p->supervisor->name }}</p>
        @endif
        <p><strong>Жанры мероприятия:</strong> {{ implode(', ', $p->technologies ?? []) }}</p>
        <p style="white-space: pre-wrap;">{{ strip_tags($p->description ?? '') }}</p>
        @if($p->github_url)
            <p><a href="{{ $p->github_url }}">GitHub</a></p>
        @endif
    @endforeach
</body>
</html>
