<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\PdfToText\Pdf;

class PortfolioPdfImportService
{
    /**
     * @return array{title: string, description: string}
     */
    public function extractFromPath(string $absolutePath, ?string $pdftotextBinary = null): array
    {
        if (! is_readable($absolutePath)) {
            throw new \RuntimeException('Невозможно прочитать PDF-файл.');
        }
        $bin = $pdftotextBinary ?: (string) (config('app.pdf_to_text_path') ?: '');

        try {
            $text = $bin !== ''
                ? Pdf::getText($absolutePath, $bin)
                : Pdf::getText($absolutePath);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Не удалось извлечь текст: '.$e->getMessage(), 0, $e);
        }
        $text = str_replace("\r\n", "\n", $text);
        $text = trim($text);
        if ($text === '') {
            throw new \RuntimeException('Не удалось извлечь текст из PDF. Убедитесь, что в файле есть текстовый слой.');
        }
        $lines = preg_split("/\R+/u", $text) ?: [];
        $meaningful = [];
        foreach ($lines as $line) {
            $t = trim(preg_replace('/\s+/u', ' ', $line) ?? '');
            if ($t === '') {
                continue;
            }
            // Skip common page markers from converted PDFs.
            if (preg_match('/^--\s*\d+\s+of\s+\d+\s*--$/iu', $t)) {
                continue;
            }
            if (preg_match('/^page\s+\d+\s+(of|\/)\s+\d+$/iu', $t)) {
                continue;
            }
            $meaningful[] = $t;
        }
        if ($meaningful === []) {
            throw new \RuntimeException('В PDF не найдено содержимого для импорта проекта.');
        }

        $title = mb_substr($meaningful[0], 0, 255);
        $descriptionLines = array_slice($meaningful, 1);
        $description = trim(implode("\n", $descriptionLines));
        if ($description === '') {
            $description = 'Описание не найдено. Добавьте детали проекта вручную.';
        }

        return [
            'title' => $title,
            'description' => $description,
        ];
    }

    /**
     * Конвертирует страницы PDF в JPG и сохраняет в project gallery.
     *
     * @return array<int, string> storage paths
     */
    public function convertPdfToGalleryImages(string $absolutePath, int $maxPages = 8): array
    {
        if (! is_readable($absolutePath)) {
            throw new \RuntimeException('Невозможно прочитать PDF-файл.');
        }

        $tmpDir = storage_path('app/tmp-imports/'.(string) Str::uuid());
        if (! @mkdir($tmpDir, 0777, true) && ! is_dir($tmpDir)) {
            throw new \RuntimeException('Не удалось создать временную папку для конвертации PDF.');
        }

        $prefix = $tmpDir.'/page';
        $pdftoppm = $this->resolvePdfToImageBinary();
        $cmd = sprintf(
            '%s -jpeg -r 150 -f 1 -l %d %s %s',
            escapeshellarg($pdftoppm),
            max(1, $maxPages),
            escapeshellarg($absolutePath),
            escapeshellarg($prefix)
        );

        $out = [];
        $code = 0;
        @exec($cmd.' 2>&1', $out, $code);
        if ($code !== 0) {
            $this->cleanupDir($tmpDir);
            throw new \RuntimeException('Не удалось конвертировать PDF в изображения. Проверьте установку pdftoppm.');
        }

        $files = glob($tmpDir.'/page-*.jpg') ?: [];
        sort($files, SORT_NATURAL);
        if ($files === []) {
            $this->cleanupDir($tmpDir);
            throw new \RuntimeException('PDF конвертирован, но страницы-изображения не найдены.');
        }

        $disk = Project::projectMediaDisk();
        $stored = [];
        foreach (array_slice($files, 0, 24) as $file) {
            $bytes = @file_get_contents($file);
            if ($bytes === false) {
                continue;
            }
            $target = 'project-gallery/'.(string) Str::uuid().'.jpg';
            Storage::disk($disk)->put($target, $bytes);
            $stored[] = $target;
        }
        $this->cleanupDir($tmpDir);

        if ($stored === []) {
            throw new \RuntimeException('Не удалось сохранить изображения из PDF в галерею.');
        }

        return $stored;
    }

    private function resolvePdfToImageBinary(): string
    {
        $explicit = (string) (config('app.pdf_to_image_path') ?: '');
        if ($explicit !== '') {
            return $explicit;
        }

        $textBin = (string) (config('app.pdf_to_text_path') ?: '');
        if ($textBin !== '') {
            $candidate = dirname($textBin).DIRECTORY_SEPARATOR
                .(str_ends_with(strtolower($textBin), '.exe') ? 'pdftoppm.exe' : 'pdftoppm');
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return strncasecmp(PHP_OS_FAMILY, 'Windows', 7) === 0 ? 'pdftoppm.exe' : 'pdftoppm';
    }

    private function cleanupDir(string $dir): void
    {
        foreach (glob($dir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
