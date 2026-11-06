<?php

namespace App\Http\Controllers;

use App\Helpers\EmojiSuggestions;
use Illuminate\Http\JsonResponse;

final class EmojiSuggestionController extends Controller
{
    public function __invoke(string $scope): JsonResponse
    {
        $data = $scope === 'filter' ? EmojiSuggestions::forFilter() : EmojiSuggestions::forNotes();

        return response()
            ->json($data, 200, [], JSON_UNESCAPED_UNICODE)
            ->header('Cache-Control', 'no-store');
    }
}
