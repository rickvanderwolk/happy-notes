<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

final class TestController extends Controller
{
    public function resetFilters(): JsonResponse
    {
        // The route itself is only registered outside production (see routes/api.php).
        // This second allowlist is here so the controller is never reachable on its own.
        if (!app()->environment(['local', 'testing'])) {
            abort(404);
        }

        User::query()->update([
            'selected_emojis' => '[]',
            'excluded_emojis' => '[]',
            'search_query' => null,
            'search_query_only' => false,
        ]);

        // Touch the first note of each user to make it appear first in the list
        $user1FirstNote = \App\Models\Note::where('user_id', 1)
            ->where('title', 'Note 1 - user 1')
            ->first();
        if ($user1FirstNote) {
            $user1FirstNote->touch();
        }

        $user2FirstNote = \App\Models\Note::where('user_id', 2)
            ->where('title', 'Note 1 - user 2')
            ->first();
        if ($user2FirstNote) {
            $user2FirstNote->touch();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Lets the infinite scroll test assert against the real total instead of a number
     * hard-coded to whatever the seeder happens to produce.
     */
    public function noteCount(): JsonResponse
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(404);
        }

        return response()->json([
            'count' => \App\Models\Note::where('user_id', 1)->count(),
        ]);
    }
}
