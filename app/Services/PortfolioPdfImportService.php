<?php

namespace App\Services;

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
        $title = 'Новый проект';
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t !== '') {
                $title = mb_substr($t, 0, 255);
                break;
            }
        }
        $rest = ltrim($text, " \n\t");
        if (str_starts_with($rest, $title)) {
            $rest = ltrim((string) substr($rest, strlen($title)));
        }
        $rest = ltrim($rest, " \n\t");
        $description = 'Описание извлечено из PDF. Отредактируйте при необходимости.';
        if ($rest !== '') {
            if (str_contains($rest, "\n\n")) {
                $parts = explode("\n\n", $rest, 2);
                if (count($parts) > 0 && trim($parts[0]) !== '') {
                    $description = trim($parts[0]);
                }
            } else {
                $description = $rest;
            }
        }
        $description = trim($description) ?: 'Описание не найдено.';

        return [
            'title' => $title,
            'description' => $description,
        ];
    }
}
