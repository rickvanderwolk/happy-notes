<?php

namespace App\Http\Controllers;

use App\Helpers\EmojiHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class FilterController extends Controller
{
    /**
     * A single emoji never comes close to this. It only exists so the filter columns
     * cannot be used to store arbitrary blobs.
     */
    private const MAX_EMOJI_BYTES = 64;

    /**
     * Stores the emoji filter. The picker is client side, so this is called on a pause in
     * clicking and once more when navigating away.
     *
     * Deliberately a plain route rather than a Livewire method: two Livewire calls in
     * quick succession during a navigation made Livewire lose its own component and drop
     * the change. A plain endpoint also lets the browser use a keepalive fetch, so a save
     * still lands when the tab is closed mid-click.
     */
    public function storeEmojis(Request $request): Response
    {
        $data = $request->validate([
            'storageKey' => 'required|in:selected_emojis,excluded_emojis',
            'emojis' => 'present|array',
            'otherEmojis' => 'present|array',
        ]);

        $otherKey = $data['storageKey'] === 'selected_emojis' ? 'excluded_emojis' : 'selected_emojis';

        $user = auth()->user();
        $user->{$data['storageKey']} = $this->sanitizeEmojis($data['emojis']);
        $user->{$otherKey} = $this->sanitizeEmojis($data['otherEmojis']);
        $user->save();

        return response()->noContent();
    }

    /**
     * Keeps only values that actually hold an emoji. Membership of the user's own emoji
     * list is deliberately not required: an emoji can drop out of that list while still
     * being a filter the user set, and silently discarding it would lose their selection.
     *
     * @param  array<array-key, mixed>  $emojis
     * @return list<string>
     */
    private function sanitizeEmojis(array $emojis): array
    {
        return array_values(array_unique(array_filter(
            $emojis,
            fn ($emoji): bool => is_string($emoji)
                && strlen($emoji) <= self::MAX_EMOJI_BYTES
                && EmojiHelper::getEmojisFromString($emoji) !== []
        )));
    }

    public function index(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('filter');
    }

    public function search(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('search');
    }

    public function clear(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $filterType = $request->input('filter_type', 'selected_emojis');

        $user = auth()->user();
        $user->$filterType = [];
        $user->save();

        return redirect()->back()->with('success', 'Filter cleared successfully');
    }

    public function clearSearch(): \Illuminate\Http\RedirectResponse
    {
        $user = auth()->user();
        $user->search_query = null;
        $user->search_query_only = false;
        $user->save();

        return redirect()->back()->with('success', 'Search cleared successfully');
    }
}
