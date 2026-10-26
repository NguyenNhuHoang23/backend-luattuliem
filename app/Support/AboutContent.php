<?php

namespace App\Support;

class AboutContent
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function fromText(?string $text): array
    {
        if ($text === null || trim($text) === '') {
            return [];
        }

        $chunks = preg_split("/\n\s*\n/", trim($text)) ?: [];

        return array_values(array_filter(array_map(function (string $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                return null;
            }

            if (str_starts_with($chunk, '## ')) {
                return [
                    'type' => 'heading',
                    'text' => trim(substr($chunk, 3)),
                ];
            }

            if (str_starts_with($chunk, '>> ')) {
                $parts = array_map('trim', explode('|', substr($chunk, 3), 2));

                return [
                    'type' => 'cta',
                    'text' => $parts[0] ?? '',
                    'link' => $parts[1] !== '' ? $parts[1] : '/tuyen-dung',
                ];
            }

            return [
                'type' => 'paragraph',
                'text' => $chunk,
            ];
        }, $chunks)));
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     */
    public static function toText(array $blocks): string
    {
        return implode("\n\n", array_map(function (array $block) {
            $type = $block['type'] ?? 'paragraph';

            if ($type === 'heading') {
                return '## '.($block['text'] ?? '');
            }

            if ($type === 'cta') {
                return '>> '.($block['text'] ?? '').' | '.($block['link'] ?? '/tuyen-dung');
            }

            if (! empty($block['parts']) && is_array($block['parts'])) {
                return implode('', array_map(
                    fn (array $part) => $part['text'] ?? '',
                    $block['parts']
                ));
            }

            return $block['text'] ?? '';
        }, $blocks));
    }
}
