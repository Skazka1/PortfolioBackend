<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.45; }
        h1 { font-size: 18pt; margin-bottom: 8px; }
        .meta { color: #444; font-size: 10pt; margin-bottom: 12px; }
        h2 { font-size: 12pt; margin-top: 12px; color: #333; }
        p { margin: 6px 0; white-space: pre-wrap; }
    </style>
</head>
<body>
    @php
        $mediaDisk = config('filesystems.project_media_disk', 'public');
        $legacyDisk = config('filesystems.default');
    @endphp
    <h1>{{ $project->title }}</h1>
    <div class="meta">
        @if($project->is_published) Опубликован @else Черновик @endif
        @if(count($project->technologies ?? []))
            | Технологии: {{ implode(', ', $project->technologies) }}
        @endif
    </div>
    <h2>Описание</h2>
    <p>{{ strip_tags($project->description ?? '') }}</p>
    @php
        $inlinePaths = \App\Support\ProjectDescriptionHtml::collectInlineStoragePaths($project->description ?? '');
    @endphp
    @if(count($inlinePaths))
        <h2>Иллюстрации в тексте</h2>
        @foreach($inlinePaths as $path)
            @php
                $src = null;
                if (\Illuminate\Support\Facades\Storage::disk($mediaDisk)->exists($path)) {
                    $src = \Illuminate\Support\Facades\Storage::disk($mediaDisk)->path($path);
                } elseif (\Illuminate\Support\Facades\Storage::disk($legacyDisk)->exists($path)) {
                    $src = \Illuminate\Support\Facades\Storage::disk($legacyDisk)->path($path);
                }
            @endphp
            @if($src)
                <div style="margin: 8px 0;">
                    <img src="{{ $src }}" style="max-width: 100%; max-height: 280px;" alt="">
                </div>
            @endif
        @endforeach
    @endif
    @php
        $gallery = $project->gallery_paths ?? [];
    @endphp
    @if(count($gallery))
        <h2>Иллюстрации</h2>
        @foreach($gallery as $path)
            @php
                $src = null;
                if (\Illuminate\Support\Facades\Storage::disk($mediaDisk)->exists($path)) {
                    $src = \Illuminate\Support\Facades\Storage::disk($mediaDisk)->path($path);
                } elseif (\Illuminate\Support\Facades\Storage::disk($legacyDisk)->exists($path)) {
                    $src = \Illuminate\Support\Facades\Storage::disk($legacyDisk)->path($path);
                }
            @endphp
            @if($src)
                <div style="margin: 8px 0;">
                    <img src="{{ $src }}" style="max-width: 100%; max-height: 280px;" alt="">
                </div>
            @endif
        @endforeach
    @endif
    @if($project->github_url)
        <p><a href="{{ $project->github_url }}">Репозиторий</a></p>
    @endif
</body>
</html>
