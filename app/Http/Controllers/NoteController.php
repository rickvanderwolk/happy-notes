<?php

namespace App\Http\Controllers;

use App\Helpers\EmojiHelper;
use app\Helpers\ProgressHelper;
use App\Models\Note;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class NoteController extends Controller
{
    public function index(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        $user = Auth::user();
        $selectedEmojis = Auth::user()->selected_emojis ?? [];
        $excludedEmojis = Auth::user()->excluded_emojis ?? [];
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
            foreach ($selectedEmojis as $emoji) {
                $notes->whereJsonContains('emojis', $emoji);
            }
            foreach ($excludedEmojis as $emoji) {
                $notes->whereJsonDoesntContain('emojis', $emoji);
            }
        }

        $notes = $notes->orderBy('updated_at', 'DESC')->paginate(15);

        return view('notes', compact('notes'));
    }

    public function show(Note $note): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        $note = Note::where(['uuid' => $note->uuid])->firstOrFail();
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

        $selectedEmojis = json_decode($request->input('selectedEmojis', '[]'), true);
        $selectedEmojis = collect($selectedEmojis)->flatten()->unique()->values()->toArray();

        $emojisInTitle = EmojiHelper::getEmojisFromString($data['title']);
        $selectedEmojis = array_merge($selectedEmojis, $emojisInTitle);

        $selectedEmojis = array_unique($selectedEmojis);

        $note = new Note();
        $note->user_id = Auth::id();
        $note->title = EmojiHelper::getStringWithoutEmojis($data['title']);
        $note->emojis = json_encode($selectedEmojis, JSON_UNESCAPED_UNICODE);
        $note->save();

        return redirect()->route('dashboard');
    }

    public function destroy(Note $note): \Illuminate\Http\RedirectResponse
    {
        $item = Note::where('uuid', $note->uuid)->firstOrFail();
        $item->delete();
        return redirect()->route('dashboard');
    }

    public function formTitle(Note $note): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        $item = Note::where('uuid', $note->uuid)->first();
        return view('notes.form-title', [
            'item' => $item,
        ]);
    }

    public function storeTitle(Request $request, Note $note): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string',
        ]);

        $note = Note::where('uuid', $note->uuid)->first();

        $selectedEmojis = $note->emojis ?? [];
        $selectedEmojis = collect($selectedEmojis)->flatten()->unique()->values()->toArray();
        $emojisInTitle = EmojiHelper::getEmojisFromString($data['title']);
        $selectedEmojis = array_merge($selectedEmojis, $emojisInTitle);
        $selectedEmojis = array_unique($selectedEmojis);

        $note->title = EmojiHelper::getStringWithoutEmojis($data['title']);
        $note->emojis = json_encode($selectedEmojis, JSON_UNESCAPED_UNICODE);
        $note->save();

        return redirect()->route('note.show', ['note' => $note->uuid]);
    }

    public function formEmojis(Note $note): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        $item = Note::where('uuid', $note->uuid)->first();
        return view('notes.form-emojis', [
            'item' => $item,
        ]);
    }

    public function storeEmojis(Request $request, Note $note): \Illuminate\Http\RedirectResponse
    {
        $item = Note::where('uuid', $note->uuid)->first();
        $selectedEmojis = json_decode($request->input('selectedEmojis', '[]'), true);
        $selectedEmojis = collect($selectedEmojis)->flatten()->unique()->values()->toArray();
        $item->emojis = json_encode($selectedEmojis, JSON_UNESCAPED_UNICODE);
        $item->save();
        return redirect()->route('note.show', ['note' => $note->uuid]);
    }

    public function storeBody(Request $request, Note $note): \Illuminate\Http\RedirectResponse
    {
        $body = $request->input('body');

        $note = Note::where('uuid', $note->uuid)->first();

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
            $selectedEmojis = array_unique($selectedEmojis);

            $note->body = json_encode($body, JSON_UNESCAPED_UNICODE);
            $note->emojis = json_encode($selectedEmojis, JSON_UNESCAPED_UNICODE);
            $note->progress = ProgressHelper::getProgressFromNoteBody($body);
        }

        $note->save();

        return redirect()->route('note.show', ['note' => $note->uuid]);
    }
}
