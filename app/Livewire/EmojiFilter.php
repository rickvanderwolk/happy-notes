<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Picking emojis from a fixed list is a client side interaction, so Alpine owns it and
 * this component only renders the starting state. Before, every single click cost a full
 * component re-render: with a few hundred emojis that is tens of kilobytes back over the
 * wire to move one emoji between two rows.
 *
 * Saving deliberately does not go through Livewire. Calling a Livewire method twice in
 * quick succession while navigating away made it lose track of its own component ("Could
 * not find Livewire component in DOM tree"), silently dropping the second change. The
 * Alpine side posts to filter.emojis.store instead.
 */
final class EmojiFilter extends Component
{
    public ?string $storageKey = null;
    public bool $updateUser = false;

    /** @var list<string> */
    public array $emojis = [];

    /**
     * The emojis picked in the opposite filter list. Selecting one here has to remove it
     * there, and the grid hides anything that sits in either list.
     *
     * @var list<string>
     */
    public array $otherEmojis = [];

    /**
     * $customEmojis comes straight from a json_decode of the note, so its keys are not
     * guaranteed to be sequential.
     *
     * @param  array<array-key, string>  $customEmojis
     */
    public function mount(?string $storageKey = null, bool $updateUser = false, array $customEmojis = []): void
    {
        $this->storageKey = $storageKey;
        $this->updateUser = $updateUser;

        $user = Auth::user();

        if ($storageKey === 'selected_emojis') {
            $this->emojis = $user->selected_emojis ?? [];
            $this->otherEmojis = $user->excluded_emojis ?? [];
        } elseif ($storageKey === 'excluded_emojis') {
            $this->emojis = $user->excluded_emojis ?? [];
            $this->otherEmojis = $user->selected_emojis ?? [];
        } else {
            $this->emojis = array_values($customEmojis);
        }
    }

    /**
     * Deliberately computed rather than a public property: with a few hundred emojis this
     * list dominates the component snapshot, which travels along on every roundtrip. As a
     * computed value it is rendered once and never serialised.
     *
     * @return list<string>
     */
    #[Computed]
    public function allEmojis(): array
    {
        return Auth::user()->all_emojis ?? [];
    }

    /**
     * Emojis that start out hidden in the grid. Rendering that state server side avoids a
     * flash of every emoji before Alpine takes over.
     *
     * @return list<string>
     */
    #[Computed]
    public function hiddenEmojis(): array
    {
        return $this->tracksOtherList()
            ? array_merge($this->emojis, $this->otherEmojis)
            : $this->emojis;
    }

    public function tracksOtherList(): bool
    {
        return in_array($this->storageKey, ['selected_emojis', 'excluded_emojis'], true);
    }

    public function render(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('livewire.emoji-filter');
    }
}
