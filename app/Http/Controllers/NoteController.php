<?php

namespace App\Http\Controllers;

use App\Helpers\EmojiHelper;
use app\Helpers\ProgressHelper;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class NoteController extends Controller
{
    /**
     * A single emoji never comes close to this. It only exists so the emojis column
     * cannot be used to store arbitrary blobs.
     */
    private const MAX_EMOJI_BYTES = 64;

    public function index(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        $user = Auth::user();
        $selectedEmojis = $user->selected_emojis ?? [];
        $excludedEmojis = $user->excluded_emojis ?? [];

        $searchQuery = $user->search_query;
        $searchQueryOnly = $user->search_query_only;

        $notes = Note::query();

        if (!empty($searchQuery)) {
            $notes->where(function ($query) use ($searchQuery) {
                $query->where('title', 'LIKE', "%{$searchQuery}%")
                    ->orWhere('body', 'LIKE', "%{$searchQuery}%");
            });
        }

        if (empty($searchQuery) || !$searchQueryOnly) {
            if (!empty($selectedEmojis)) {
                foreach ($selectedEmojis as $emoji) {
                    $notes->whereJsonContains('emojis', $emoji);
                }
            }
            if (!empty($excludedEmojis)) {
                foreach ($excludedEmojis as $emoji) {
                    $notes->whereJsonDoesntContain('emojis', $emoji);
                }
            }
        }

        $notes = $notes->orderBy('updated_at', 'DESC')->paginate(15);

        return view('notes', compact('notes'));
    }

    public function show(Note $note): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        $note->body = json_decode($note->body, true);
        return view('notes.show', compact('note'));
    }

    public function create(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('new');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string',
            'selectedEmojis' => 'nullable|string',
        ]);

        $selectedEmojis = $this->sanitizeEmojis($request->input('selectedEmojis'));

        $emojisInTitle = EmojiHelper::getEmojisFromString($data['title']);
        $selectedEmojis = array_merge($selectedEmojis, $emojisInTitle);

        $selectedEmojis = array_values(array_unique($selectedEmojis));

        $note = new Note();
        $note->user_id = Auth::id();
        $note->title = EmojiHelper::getStringWithoutEmojis($data['title']);
        $note->emojis = json_encode($selectedEmojis, JSON_UNESCAPED_UNICODE);
        $note->save();

        return redirect()->route('dashboard');
    }

    public function destroy(Note $note): \Illuminate\Http\RedirectResponse
    {
        $note->delete();
        return redirect()->route('dashboard');
    }

    public function formTitle(Note $note): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('notes.form-title', [
            'item' => $note,
        ]);
    }

    public function storeTitle(Request $request, Note $note): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string',
        ]);

        $selectedEmojis = $note->emojis ?? [];
        $selectedEmojis = collect($selectedEmojis)->flatten()->unique()->values()->toArray();
        $emojisInTitle = EmojiHelper::getEmojisFromString($data['title']);
        $selectedEmojis = array_merge($selectedEmojis, $emojisInTitle);
        $selectedEmojis = array_values(array_unique($selectedEmojis));

        $note->title = EmojiHelper::getStringWithoutEmojis($data['title']);
        $note->emojis = json_encode($selectedEmojis, JSON_UNESCAPED_UNICODE);
        $note->save();

        return redirect()->route('note.show', ['note' => $note->uuid]);
    }

    public function formEmojis(Note $note): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('notes.form-emojis', [
            'item' => $note,
        ]);
    }

    public function storeEmojis(Request $request, Note $note): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'selectedEmojis' => 'nullable|string',
        ]);

        $selectedEmojis = $this->sanitizeEmojis($request->input('selectedEmojis'));
        $note->emojis = json_encode($selectedEmojis, JSON_UNESCAPED_UNICODE);
        $note->save();
        return redirect()->route('note.show', ['note' => $note->uuid]);
    }

    public function storeBody(Request $request, Note $note): \Illuminate\Http\RedirectResponse
    {
        // A type check, not a size limit: the editor always posts an object here, so this
        // can never reject a real save. No byte or block ceiling until editor.js can
        // actually report a rejected save back to the user.
        $request->validate([
            'body' => 'nullable|array',
        ]);

        $body = $request->input('body');

        if (empty($body)) {
            $note->body = null;
            $note->progress = null;
        } else {
            $selectedEmojis = $note->emojis ?? [];
            $selectedEmojis = collect($selectedEmojis)->flatten()->unique()->values()->toArray();
            if (!empty($body['blocks'])) {
                $bodyContent = array_map(fn ($block) => $block['data']['text'] ?? '', $body['blocks']);
                $bodyContent = implode(" ", $bodyContent);
                $emojisInBody = EmojiHelper::getEmojisFromString($bodyContent);
                $selectedEmojis = array_merge($selectedEmojis, $emojisInBody);
            }
            $selectedEmojis = array_values(array_unique($selectedEmojis));

            $note->body = json_encode($body, JSON_UNESCAPED_UNICODE);
            $note->emojis = json_encode($selectedEmojis, JSON_UNESCAPED_UNICODE);
            $note->progress = ProgressHelper::getProgressFromNoteBody($body);
        }

        $note->save();

        return redirect()->route('note.show', ['note' => $note->uuid]);
    }

    /**
     * The emoji list arrives as a hidden input, so it is user input like any other and
     * cannot be trusted to contain emojis at all. This only ever drops values that are
     * not emojis, so it can never throw away something a user actually picked, and there
     * is deliberately no cap on how many emojis a note may carry.
     *
     * @return list<string>
     */
    private function sanitizeEmojis(mixed $value): array
    {
        $emojis = is_string($value) ? json_decode($value, true) : $value;

        if (!is_array($emojis)) {
            return [];
        }

        return collect($emojis)
            ->flatten()
            ->filter(fn ($emoji): bool => is_string($emoji)
                && strlen($emoji) <= self::MAX_EMOJI_BYTES
                && EmojiHelper::getEmojisFromString($emoji) !== [])
            ->unique()
            ->values()
            ->toArray();
    }
}
