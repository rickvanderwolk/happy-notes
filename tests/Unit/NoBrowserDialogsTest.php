<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The app has its own dialog (resources/js/dialog.js) so every confirmation looks the
 * same. This keeps the browser's own dialogs from creeping back in.
 */
class NoBrowserDialogsTest extends TestCase
{
    private const PATTERN = '/\b(alert|confirm|prompt)\(|wire:confirm/';

    public function test_resources_do_not_use_the_browser_dialogs(): void
    {
        $resources = dirname(__DIR__, 2) . '/resources';
        $found = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resources));
        foreach ($files as $file) {
            if (!$file->isFile() || !preg_match('/\.(js|php)$/', $file->getFilename())) {
                continue;
            }

            foreach (file($file->getPathname()) as $number => $line) {
                if (preg_match(self::PATTERN, $line)) {
                    $found[] = str_replace($resources . '/', '', $file->getPathname()) . ':' . ($number + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $found,
            'Use data-confirm, confirmDialog() or alertDialog() from resources/js/dialog.js instead.'
        );
    }
}
