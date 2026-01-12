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
        $notes = Note::where('user_id', Auth::id())->get();

        $stats = [];
        foreach ($notes as $note) {
            $emojis = $note->emojis ?? [];
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
        $user->selected_emojis = json_encode([$emoji], JSON_UNESCAPED_UNICODE);
        $user->excluded_emojis = json_encode([], JSON_UNESCAPED_UNICODE);
        $user->save();

        return redirect()->route('notes.show');
    }

    public function removeEmoji(string $emoji): void
    {
        $notes = Note::where('user_id', Auth::id())
            ->whereJsonContains('emojis', $emoji)
            ->get();

        foreach ($notes as $note) {
            $emojis = $note->emojis ?? [];
            if (count($emojis) > 1) {
                $emojis = array_values(array_filter($emojis, fn ($e) => $e !== $emoji));
                $note->emojis = json_encode($emojis, JSON_UNESCAPED_UNICODE);
                $note->save();
            }
        }

        $this->loadStats();
    }

    public function render(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('livewire.cleanup');
    }
}
