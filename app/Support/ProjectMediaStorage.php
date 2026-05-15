<?php

namespace App\Support;

use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Загрузка файлов проекта в PROJECT_MEDIA_DISK (Yandex Object Storage / S3). */
final class ProjectMediaStorage
{
    public static function storeUploaded(UploadedFile $file, string $directory): string
    {
        $disk = Project::projectMediaDisk();
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $path = trim($directory, '/').'/'.(string) Str::uuid().'.'.$ext;

        Storage::disk($disk)->put(
            $path,
            file_get_contents($file->getRealPath()),
            static::uploadOptions($disk),
        );

        return $path;
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        Storage::disk(Project::projectMediaDisk())->delete($path);
    }

    /**
     * @return array<string, mixed>
     */
    public static function uploadOptions(string $disk): array
    {
        if ($disk !== 's3') {
            return [];
        }

        return ['visibility' => 'public'];
    }
}
