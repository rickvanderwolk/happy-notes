<?php

namespace App\Helpers;

use App\Models\Note;
use App\Models\User;

final class LogoVariant
{
    public const APRIL_FOOLS = 'april-fools';
    public const EMOJI_DAY = 'emoji-day';

    private const DATES = [
        '04-01' => self::APRIL_FOOLS,
        '07-17' => self::EMOJI_DAY,
    ];

    private const MAX_EMOJIS = 7;
    private const MAX_NOTES = 500;

    public static function current(): ?string
    {
        return self::DATES[now()->format('m-d')] ?? null;
    }

    /**
     * The emojis the logo turns into on emoji day: the ones the user picks most, from their
     * most recent notes.
     *
     * @return list<string>
     */
    public static function emojis(?User $user): array
    {
        if ($user === null) {
            return User::WELCOME_EMOJIS;
        }

        $counts = [];

        Note::query()
            ->latest()
            ->limit(self::MAX_NOTES)
            ->pluck('emojis')
            ->each(function (?array $emojis) use (&$counts): void {
                foreach (array_unique($emojis ?? []) as $emoji) {
                    $counts[$emoji] = ($counts[$emoji] ?? 0) + 1;
                }
            });

        if ($counts === []) {
            return User::WELCOME_EMOJIS;
        }

        arsort($counts);

        return array_map('strval', array_slice(array_keys($counts), 0, self::MAX_EMOJIS));
    }
}
