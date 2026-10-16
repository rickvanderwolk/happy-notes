<?php

namespace App\Helpers;

final class CelebrationHelper
{
    /**
     * Milestones are repdigits (222, 333, 1111, ...), plus 99 as the first one. 111 is
     * skipped because it would follow 99 too closely.
     */
    private const FIRST_MILESTONE = 99;

    private const MESSAGES = [
        99 => 'Not one problem.',
        222 => 'Twice as nice.',
        333 => 'Good things come in threes.',
        444 => 'Four of a kind.',
        555 => 'High five!',
        666 => 'Devilishly happy.',
        777 => 'Jackpot.',
        888 => 'Tip it over: forever.',
        999 => 'One more digit to go.',
        1111 => 'Make a wish.',
        2222 => 'Double the wishes.',
        3333 => 'Threes all the way down.',
        4444 => 'Four of a kind, again.',
        5555 => 'High ten.',
        6666 => 'Still devilishly happy.',
        7777 => 'Mega jackpot.',
        8888 => 'Forever, twice.',
        9999 => 'One more digit to go. Again.',
    ];

    private const MESSAGE_BEYOND = "Okay, now you're just showing off.";

    public static function isMilestone(int $count): bool
    {
        if ($count === self::FIRST_MILESTONE) {
            return true;
        }

        if ($count < 222) {
            return false;
        }

        $digits = (string) $count;

        return $digits === str_repeat($digits[0], strlen($digits));
    }

    /**
     * The highest milestone a user with this many notes has passed, or 0 for none.
     */
    public static function highestMilestoneUpTo(int $count): int
    {
        $highest = 0;

        for ($length = 2; ; $length++) {
            for ($digit = 1; $digit <= 9; $digit++) {
                $candidate = (int) str_repeat((string) $digit, $length);

                if ($candidate > $count) {
                    return $highest;
                }

                if (self::isMilestone($candidate)) {
                    $highest = $candidate;
                }
            }
        }
    }

    public static function milestoneMessage(int $count): string
    {
        return self::MESSAGES[$count] ?? self::MESSAGE_BEYOND;
    }
}
