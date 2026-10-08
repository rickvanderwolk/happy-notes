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

    private const NOTES_PER_PAGE = 15;

    public function index(
        Request $request
    ): \Illuminate\View\View|\Illuminate\Contracts\View\View|\Illuminate\Http\Response {
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

        // Only the columns a note card actually renders. 'progress' is its own column and
        // drives the progress bar, so it has to be listed explicitly. Leaving 'body' out
        // is the point: it is by far the largest column and the card never shows it.
        //
        // simplePaginate instead of paginate because the list uses infinite scroll and
        // hides the page links entirely. paginate() ran a COUNT(*) over the whole filtered
        // set on every load and then threw the result away, which with an emoji filter or
        // a search term means a second full scan for nothing.
        $columns = ['id', 'uuid', 'title', 'emojis', 'progress'];

        // id breaks ties, so notes saved within the same second cannot swap places between
        // two page requests and show up twice or not at all.
        $notes = $notes
            ->orderBy('updated_at', 'DESC')
            ->orderBy('id', 'DESC');

        // Closing a note returns to the list at that note. Rather than the browser fetching
        // page after page until it shows up, return every page up to and including the one
        // holding it in one go, and say which page that was so infinite scroll continues
        // from there.
        if ($request->boolean('partial') && $request->filled('until')) {
            $page = max(1, $request->integer('page', 1));
            $position = (clone $notes)->pluck('uuid')->search($request->string('until')->toString());

            if ($position === false) {
                return response()->view('notes.partials.cards', ['notes' => []])
                    ->header('X-Last-Page', (string) ($page - 1));
            }

            $lastPage = max($page, intdiv($position, self::NOTES_PER_PAGE) + 1);

            $notes = $notes
                ->offset(($page - 1) * self::NOTES_PER_PAGE)
                ->limit(($lastPage - $page + 1) * self::NOTES_PER_PAGE)
                ->get($columns);

            return response()->view('notes.partials.cards', compact('notes'))
                ->header('X-Last-Page', (string) $lastPage);
        }

        $notes = $notes->simplePaginate(self::NOTES_PER_PAGE, $columns);

        // Infinite scroll asks for the cards on their own. Same query, same partial as the
        // full page uses, so a scroll batch can never show a different set of notes than a
        // normal page load would.
        if ($request->boolean('partial')) {
            return view('notes.partials.cards', compact('notes'));
        }

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

    public function storeBody(
        Request $request,
        Note $note
    ): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse {
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

        // The editor autosaves in the background. Redirecting made it render and download
        // the whole note page on every keystroke pause, only to throw it away. The progress
        // goes along so the editor can update the progress bar without a second request.
        if ($request->expectsJson()) {
            return response()->json(['progress' => $note->progress]);
        }

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
