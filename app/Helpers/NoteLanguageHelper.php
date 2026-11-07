<?php

namespace App\Helpers;

use Illuminate\Http\Request;

final class NoteLanguageHelper
{
    /**
     * The interface is English, but users write their notes in their own language. Screen
     * readers pick their voice from the lang attribute, so text the user wrote is marked
     * with the browser's preferred language instead of the app's.
     */
    public static function fromRequest(Request $request): string
    {
        $language = str_replace('_', '-', (string) $request->getPreferredLanguage());

        if (preg_match('/^[a-z]{2,3}(-[A-Za-z0-9]{2,8})*$/', $language) !== 1) {
            return str_replace('_', '-', app()->getLocale());
        }

        return $language;
    }
}
