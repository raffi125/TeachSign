<?php

namespace App\Support;

use Illuminate\Support\Str;

class MediaEmbed
{
    /**
     * @return array{type: string, embed: ?string}
     */
    public static function parse(?string $url): array
    {
        if ($url === null || trim($url) === '') {
            return ['type' => 'none', 'embed' => null];
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if ($scheme !== null && ! in_array(Str::lower($scheme), ['http', 'https'], true)) {
            return ['type' => 'none', 'embed' => null];
        }

        $lower = Str::lower($url);

        if (preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{6,})~i', $url, $matches)) {
            return [
                'type' => 'youtube',
                'embed' => 'https://www.youtube-nocookie.com/embed/'.$matches[1],
            ];
        }

        if (Str::contains($lower, 'drive.google.com')) {
            $id = null;

            if (preg_match('~/file/d/([A-Za-z0-9_-]+)~i', $url, $matches)) {
                $id = $matches[1];
            } elseif (preg_match('~[?&]id=([A-Za-z0-9_-]+)~i', $url, $matches)) {
                $id = $matches[1];
            }

            if ($id !== null) {
                return [
                    'type' => 'drive',
                    'embed' => 'https://drive.google.com/file/d/'.$id.'/preview',
                ];
            }
        }

        $path = Str::before($lower, '?');

        if (Str::endsWith($path, ['.mp4', '.webm', '.ogg'])) {
            return ['type' => 'video', 'embed' => null];
        }

        if (Str::endsWith($path, ['.jpg', '.jpeg', '.png', '.gif', '.webp'])) {
            return ['type' => 'image', 'embed' => null];
        }

        return ['type' => 'link', 'embed' => null];
    }
}
