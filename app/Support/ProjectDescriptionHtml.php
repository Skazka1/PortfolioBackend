<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class ProjectDescriptionHtml
{
    /**
     * Оставляет безопасный подмножество HTML и только разрешённые src у img.
     */
    public static function sanitize(string $html, int $projectId): string
    {
        $html = trim($html);
        if ($html === '' || $html === '<p></p>') {
            return '';
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $enc = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8');
        $dom->loadHTML('<div>'.$enc.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);

        foreach ($xpath->query('//*[local-name()="script"]|//*[local-name()="iframe"]|//*[local-name()="style"]|//*[local-name()="object"]|//*[local-name()="embed"]') ?: [] as $bad) {
            if ($bad->parentNode) {
                $bad->parentNode->removeChild($bad);
            }
        }

        foreach ($xpath->query('//*[local-name()="img"]') ?: [] as $img) {
            if (! $img instanceof DOMElement) {
                continue;
            }
            $src = $img->getAttribute('src');
            if (! self::isAllowedImgSrc($src, $projectId)) {
                if ($img->parentNode) {
                    $img->parentNode->removeChild($img);
                }

                continue;
            }
            foreach (['onerror', 'onload', 'onclick', 'onmouseover'] as $attr) {
                $img->removeAttribute($attr);
            }
        }

        foreach ($xpath->query('//*[local-name()="a"]') ?: [] as $a) {
            if (! $a instanceof DOMElement) {
                continue;
            }
            $href = $a->getAttribute('href');
            if ($href !== '' && ! preg_match('#^(https?:)?//#i', $href) && ! str_starts_with($href, 'mailto:')) {
                $a->removeAttribute('href');
            }
        }

        $root = $dom->documentElement;
        if (! $root) {
            libxml_clear_errors();

            return '';
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        libxml_clear_errors();

        return html_entity_decode(trim($out), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function isAllowedImgSrc(string $src, int $projectId): bool
    {
        if ($src === '') {
            return false;
        }
        $pid = preg_quote((string) $projectId, '~');

        return (bool) preg_match(
            '~/(?:storage/)?project-inline/'.$pid.'/[a-zA-Z0-9_.-]+\.(?:jpg|jpeg|png|gif|webp)(?:\?[^\s]*)?$~i',
            $src
        );
    }

    /**
     * @return array<int, string> пути относительно диска, например project-inline/3/uuid.jpg
     */
    public static function collectInlineStoragePaths(string $html): array
    {
        if ($html === '') {
            return [];
        }
        preg_match_all('#project-inline/(\d+)/([a-zA-Z0-9_.-]+\.(?:jpg|jpeg|png|gif|webp))#i', $html, $m, PREG_SET_ORDER);
        $paths = [];
        foreach ($m as $row) {
            $paths[] = 'project-inline/'.$row[1].'/'.$row[2];
        }

        return array_values(array_unique($paths));
    }
}
