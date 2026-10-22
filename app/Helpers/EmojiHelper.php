<?php

namespace App\Helpers;

final class EmojiHelper
{
    /**
     * Determines if a single Unicode code point is an emoji (excluding digits).
     */
    private static function isSingleEmoji(string $char): bool
    {
        // Matches exactly one code point that is an emoji but not a digit.
        return (bool) preg_match('/^\p{Extended_Pictographic}$/u', $char);
    }

    /**
     * Scans for keycap emojis (e.g. "10️⃣"), or standard emojis (🍏, 😊),
     * and ignores plain digits ("10", "3").
     *
     * @return list<string>
     */
    public static function getEmojisFromString(string $string): array
    {
        $result = [];
        $length = mb_strlen($string);
        $i = 0;

        while ($i < $length) {
            // Check for "<digits> FE0F 20E3" (keycap emojis like "3️⃣", "10️⃣")
            // Gather as many consecutive digits as possible, then check for FE0F+20E3
            $chunk = mb_substr($string, $i, 1);
            if (preg_match('/^\d$/u', $chunk)) {
                $j = $i;
                $digits = '';

                // Collect all consecutive digits
                while ($j < $length && preg_match('/^\d$/u', mb_substr($string, $j, 1))) {
                    $digits .= mb_substr($string, $j, 1);
                    $j++;
                }

                // Check if FE0F + 20E3 follows
                $nextChunk = mb_substr($string, $j, 2);
                $keycapStr = $digits . $nextChunk; // e.g. "10" + "️⃣"

                // If it matches <digits> FE0F 20E3, it's a keycap emoji
                if (preg_match('/^\d+\x{FE0F}\x{20E3}$/u', $keycapStr)) {
                    $result[] = $keycapStr;
                    $i = $j + 2;
                    continue;
                }
                // Otherwise, just move on; we'll process char by char
            }

            // If not a keycap, check a single code point
            $char = mb_substr($string, $i, 1);
            if (self::isSingleEmoji($char)) {
                $result[] = $char;
            }
            $i++;
        }

        // Return unique emojis (in case of duplicates)
        return array_values(array_unique($result));
    }

    /**
     * Removes all emojis, but keeps plain digits.
     * Keycap emojis ("3️⃣", "10️⃣") become their digits ("3", "10").
     * Other emojis (🍏, 😊) are removed.
     */
    public static function getStringWithoutEmojis(string $string): string
    {
        $result = '';
        $length = mb_strlen($string);
        $i = 0;

        while ($i < $length) {
            $char = mb_substr($string, $i, 1);

            // Check if we have a (multi-digit) keycap emoji
            if (preg_match('/^\d$/u', $char)) {
                $j = $i;
                $digits = '';

                // Collect all consecutive digits
                while ($j < $length && preg_match('/^\d$/u', mb_substr($string, $j, 1))) {
                    $digits .= mb_substr($string, $j, 1);
                    $j++;
                }

                // Check if FE0F + 20E3 follows
                $nextChunk = mb_substr($string, $j, 2);
                $keycapStr = $digits . $nextChunk;

                if (preg_match('/^\d+\x{FE0F}\x{20E3}$/u', $keycapStr)) {
                    // Replace with the digits (e.g., "10️⃣" -> "10")
                    $result .= $digits;
                    $i = $j + 2;
                    continue;
                }

                // Not a keycap emoji -> keep the collected digits
                $result .= $digits;
                $i = $j;
                continue;
            }

            // Otherwise check if this single code point is an emoji
            if (self::isSingleEmoji($char)) {
                // Skip
                $i++;
                continue;
            }

            // Regular character -> keep it
            $result .= $char;
            $i++;
        }

        return $result;
    }
}
