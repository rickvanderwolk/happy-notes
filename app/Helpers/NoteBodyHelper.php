<?php

namespace App\Helpers;

final class NoteBodyHelper
{
    private const SKIPPED_KEYS = ['style', 'meta'];

    public static function toPlainText(?string $body): ?string
    {
        if ($body === null || trim($body) === '') {
            return null;
        }

        $decoded = json_decode($body, true);

        if (!is_array($decoded)) {
            return self::clean($body);
        }

        $parts = [];
        foreach ($decoded['blocks'] ?? [] as $block) {
            if (is_array($block) && is_array($block['data'] ?? null)) {
                self::collectStrings($block['data'], $parts);
            }
        }

        return self::clean(implode(' ', $parts));
    }

    /**
     * @param array<mixed> $data
     * @param list<string> $parts
     */
    private static function collectStrings(array $data, array &$parts): void
    {
        foreach ($data as $key => $value) {
            if (in_array($key, self::SKIPPED_KEYS, true)) {
                continue;
            }

            if (is_string($value)) {
                $parts[] = $value;
            } elseif (is_array($value)) {
                self::collectStrings($value, $parts);
            }
        }
    }

    private static function clean(string $text): ?string
    {
        $text = (string) preg_replace('/<br\s*\/?>/i', ' ', $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/[\s\x{00A0}]+/u', ' ', $text));

        return $text === '' ? null : $text;
    }
}
