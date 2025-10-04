<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class EmojiFilter extends Component
{
    public $allEmojis = [];
    public $emojis = [];
    public $storageKey;
    public $updateUser = false;

    // Cache user emoji data to avoid repeated Auth::user() calls
    public $selectedEmojisCache = [];
    public $excludedEmojisCache = [];

    public function mount($storageKey = null, $updateUser = false, $customEmojis = []): void
    {
        $this->storageKey = $storageKey;
        $this->updateUser = $updateUser;

        $user = Auth::user();

        $this->allEmojis = is_string($user->all_emojis)
            ? json_decode($user->all_emojis, true)
            : $user->all_emojis;
        $this->allEmojis = $this->allEmojis ?? [];

        // Cache user emoji data
        $this->selectedEmojisCache = is_string($user->selected_emojis)
            ? json_decode($user->selected_emojis, true)
            : $user->selected_emojis;
        $this->selectedEmojisCache = $this->selectedEmojisCache ?? [];

        $this->excludedEmojisCache = is_string($user->excluded_emojis)
            ? json_decode($user->excluded_emojis, true)
            : $user->excluded_emojis;
        $this->excludedEmojisCache = $this->excludedEmojisCache ?? [];

        if ($this->storageKey && $user->{$this->storageKey}) {
            $this->emojis = is_string($user->{$this->storageKey})
                ? json_decode($user->{$this->storageKey}, true)
                : $user->{$this->storageKey};
        } elseif (!$this->storageKey && !$this->updateUser) {
            $this->emojis = $customEmojis;
            if (!empty($this->emojis)) {
                $this->dispatch('emojisChanged', [$this->emojis]);
            }
        }
    }

    /**
     * @return void
     */
    public function selectEmoji($emoji)
    {
        if (in_array($emoji, $this->emojis)) {
            return;
        }

        if ($this->storageKey === 'selected_emojis') {
            $this->excludedEmojisCache = array_values(array_filter($this->excludedEmojisCache, fn ($e) => $e !== $emoji));
            $this->emojis[] = $emoji;
            $this->selectedEmojisCache = $this->emojis;
        } elseif ($this->storageKey === 'excluded_emojis') {
            $this->selectedEmojisCache = array_values(array_filter($this->selectedEmojisCache, fn ($e) => $e !== $emoji));
            $this->emojis[] = $emoji;
            $this->excludedEmojisCache = $this->emojis;
        } else {
            $this->emojis[] = $emoji;
        }

        if ($this->updateUser) {
            $user = Auth::user();
            $user->selected_emojis = $this->selectedEmojisCache;
            $user->excluded_emojis = $this->excludedEmojisCache;
            $user->save();
        }

        $this->dispatch('emojisChanged', [$this->emojis]);
        $this->dispatch('filterUpdated');
    }

    public function deselectEmoji($emoji): void
    {
        $this->emojis = array_values(array_filter($this->emojis, fn ($e) => $e !== $emoji));

        // Update cache
        if ($this->storageKey === 'selected_emojis') {
            $this->selectedEmojisCache = $this->emojis;
        } elseif ($this->storageKey === 'excluded_emojis') {
            $this->excludedEmojisCache = $this->emojis;
        }

        if ($this->updateUser && $this->storageKey) {
            Auth::user()->update([
                $this->storageKey => $this->emojis
            ]);
        }
        $this->dispatch('emojisChanged', [$this->emojis]);
        $this->dispatch('filterUpdated');
    }

    public function deselectAll(): void
    {
        $this->emojis = [];

        // Update cache
        if ($this->storageKey === 'selected_emojis') {
            $this->selectedEmojisCache = [];
        } elseif ($this->storageKey === 'excluded_emojis') {
            $this->excludedEmojisCache = [];
        }

        if ($this->updateUser && $this->storageKey) {
            Auth::user()->update([
                $this->storageKey => $this->emojis
            ]);
        }
        $this->dispatch('emojisChanged', [$this->emojis]);
        $this->dispatch('filterUpdated');
    }

    #[Computed]
    public function selectableEmojis(): array
    {
        return array_filter($this->allEmojis, function ($emoji) {
            if ($this->storageKey === 'selected_emojis' || $this->storageKey === 'excluded_emojis') {
                return ! in_array($emoji, $this->selectedEmojisCache) && ! in_array($emoji, $this->excludedEmojisCache);
            }
            return ! in_array($emoji, $this->emojis);
        });
    }

    public function render(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('livewire.emoji-filter', [
            'currentEmojis' => $this->emojis
        ]);
    }
}
