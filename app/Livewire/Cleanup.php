<?php

namespace App\Livewire;

use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class Cleanup extends Component
{
    public array $emojiStats = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        // Only the emojis column: counting emojis never needed the note bodies. pluck()
        // runs the values through getEmojisAttribute(), so these are decoded arrays.
        /** @var iterable<int, list<string>|null> $emojisPerNote */
        $emojisPerNote = Note::where('user_id', Auth::id())->pluck('emojis');

        $stats = [];
        foreach ($emojisPerNote as $noteEmojis) {
            $emojis = $noteEmojis ?? [];
            foreach ($emojis as $emoji) {
                if (!isset($stats[$emoji])) {
                    $stats[$emoji] = [
                        'count' => 0,
                        'canRemove' => true,
                    ];
                }
                $stats[$emoji]['count']++;

                if (count($emojis) <= 1) {
                    $stats[$emoji]['canRemove'] = false;
                }
            }
        }

        uasort($stats, fn ($a, $b) => $a['count'] <=> $b['count']);

        $this->emojiStats = $stats;
    }

    public function filterByEmoji(string $emoji): mixed
    {
        $user = Auth::user();
        $user->selected_emojis = [$emoji];
        $user->excluded_emojis = [];
        $user->save();

        return redirect()->route('notes.show');
    }

    public function removeEmoji(string $emoji): void
    {
        $notes = Note::where('user_id', Auth::id())
            ->whereJsonContains('emojis', $emoji)
            ->get();

        $lastChanged = null;

        // Every save fires Note::updateUserEmojis(), which walks the user's entire note
        // list. Doing that once per note made removing a widely used emoji quadratic, so
        // the model events are suppressed here and the emoji list is rebuilt once at the
        // end instead.
        Note::withoutEvents(function () use ($notes, $emoji, &$lastChanged) {
            foreach ($notes as $note) {
                $emojis = $note->emojis ?? [];
                if (count($emojis) > 1) {
                    $emojis = array_values(array_filter($emojis, fn ($e) => $e !== $emoji));
                    $note->emojis = json_encode($emojis, JSON_UNESCAPED_UNICODE);
                    $note->save();
                    $lastChanged = $note;
                }
            }
        });

        $lastChanged?->updateUserEmojis();

        $this->loadStats();
    }

    public function render(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('livewire.cleanup');
    }
}
