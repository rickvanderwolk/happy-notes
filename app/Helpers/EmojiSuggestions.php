<?php

namespace App\Helpers;

use App\Casts\EmojiArrayCast;
use App\Models\Note;
use App\Models\User;

final class EmojiSuggestions
{
    private const MAX_NOTES_FOR_WORDS = 1000;
    private const MAX_EMOJIS_PER_ENTRY = 5;
    private const MIN_NOTES_PER_WORD = 2;
    private const MIN_SCORE = 0.1;

    public static function version(User $user): string
    {
        return md5(implode('|', [
            $user->id,
            Note::query()->count(),
            implode(',', $user->all_emojis ?? []),
        ]));
    }

    /**
     * @return array{words: array<array-key, array<array-key, float>>, pairs: array<array-key, array<array-key, float>>}
     */
    public static function forNotes(): array
    {
        return ['words' => self::wordScores(), 'pairs' => self::pairScores()];
    }

    /**
     * @return array{sets: list<array{e: list<string>, n: int}>}
     */
    public static function forFilter(): array
    {
        return ['sets' => self::sets()];
    }

    /**
     * @return list<array{e: list<string>, n: int}>
     */
    private static function sets(): array
    {
        $sets = [];

        foreach (Note::query()->toBase()->get(['emojis']) as $note) {
            $emojis = array_values(array_unique(self::decodeEmojis($note->emojis)));
            sort($emojis);
            $key = implode("\0", $emojis);
            $sets[$key] ??= ['e' => $emojis, 'n' => 0];
            $sets[$key]['n']++;
        }

        return array_values($sets);
    }

    /**
     * @return array<array-key, array<array-key, float>>
     */
    private static function pairScores(): array
    {
        $total = 0;
        $emojiCounts = [];
        $pairCounts = [];

        foreach (self::sets() as $set) {
            $total += $set['n'];

            foreach ($set['e'] as $emoji) {
                $emojiCounts[$emoji] = ($emojiCounts[$emoji] ?? 0) + $set['n'];

                foreach ($set['e'] as $other) {
                    if ($other !== $emoji) {
                        $pairCounts[$emoji][$other] = ($pairCounts[$emoji][$other] ?? 0) + $set['n'];
                    }
                }
            }
        }

        $scores = [];

        foreach ($pairCounts as $emoji => $counts) {
            $entry = [];

            foreach ($counts as $other => $count) {
                $entry[$other] = $count / $emojiCounts[$emoji] - $emojiCounts[$other] / $total;
            }

            $scores[$emoji] = self::strongest($entry);
        }

        return array_filter($scores);
    }

    /**
     * @return array<array-key, array<array-key, float>>
     */
    private static function wordScores(): array
    {
        $notes = Note::query()
            ->where('title', '!=', User::welcomeNoteTitle())
            ->latest('id')
            ->limit(self::MAX_NOTES_FOR_WORDS)
            ->toBase()
            ->get(['title', 'emojis']);

        $emojiCounts = [];
        $wordCounts = [];
        $pairCounts = [];

        foreach ($notes as $note) {
            $emojis = array_unique(self::decodeEmojis($note->emojis));
            preg_match_all('/[\p{L}\p{N}]{3,}/u', mb_strtolower((string) $note->title), $matches);

            foreach ($emojis as $emoji) {
                $emojiCounts[$emoji] = ($emojiCounts[$emoji] ?? 0) + 1;
            }

            foreach (array_unique($matches[0]) as $word) {
                $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;

                foreach ($emojis as $emoji) {
                    $pairCounts[$word][$emoji] = ($pairCounts[$word][$emoji] ?? 0) + 1;
                }
            }
        }

        $scores = [];

        foreach ($pairCounts as $word => $counts) {
            if ($wordCounts[$word] < self::MIN_NOTES_PER_WORD) {
                continue;
            }

            $entry = [];

            foreach ($counts as $emoji => $count) {
                $entry[$emoji] = $count / $wordCounts[$word] - $emojiCounts[$emoji] / $notes->count();
            }

            $scores[$word] = self::strongest($entry);
        }

        return array_filter($scores);
    }

    /**
     * @param  array<array-key, float>  $entry
     * @return array<array-key, float>
     */
    private static function strongest(array $entry): array
    {
        arsort($entry);

        $strongest = array_filter($entry, fn (float $score): bool => $score >= self::MIN_SCORE);

        return array_map(
            fn (float $score): float => round($score, 2),
            array_slice($strongest, 0, self::MAX_EMOJIS_PER_ENTRY, true)
        );
    }

    /**
     * @return list<string>
     */
    private static function decodeEmojis(mixed $value): array
    {
        return (new EmojiArrayCast())->get(new Note(), 'emojis', $value, []) ?? [];
    }
}
