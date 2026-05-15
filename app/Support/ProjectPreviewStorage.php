<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/** Сохранение превью проекта (Yandex Object Storage / S3 — см. PROJECT_MEDIA_DISK). */
final class ProjectPreviewStorage
{
    public static function store(UploadedFile $file): string
    {
        return ProjectMediaStorage::storeUploaded($file, 'project-previews');
    }

    public static function delete(?string $path): void
    {
        ProjectMediaStorage::delete($path);
    }
}
