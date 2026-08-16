<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Picking emojis from a fixed list is a client side interaction, so Alpine owns it and
 * the server only hears about the end result. Before, every single click cost a full
 * component re-render: with a few hundred emojis that is tens of kilobytes back over the
 * wire to move one emoji between two rows.
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
     * Called debounced from Alpine, so only on a pause in clicking rather than per click.
     *
     * @param  list<string>  $emojis
     * @param  list<string>  $otherEmojis
     */
    public function persist(array $emojis, array $otherEmojis): void
    {
        // The browser already shows the new state, so there is nothing to send back.
        // Without this Livewire would re-render the whole grid on every save.
        $this->skipRender();

        if (!$this->updateUser || $this->storageKey === null) {
            return;
        }

        // The selection now arrives from the client, so it is untrusted input: keep only
        // emojis this user actually has.
        $known = $this->allEmojis();
        $this->emojis = $this->onlyKnown($emojis, $known);
        $this->otherEmojis = $this->onlyKnown($otherEmojis, $known);

        $otherKey = $this->storageKey === 'selected_emojis' ? 'excluded_emojis' : 'selected_emojis';

        $user = Auth::user();
        $user->{$this->storageKey} = $this->emojis;
        $user->{$otherKey} = $this->otherEmojis;
        $user->save();
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

    /**
     * @param  array<int, mixed>  $emojis
     * @param  list<string>  $known
     * @return list<string>
     */
    private function onlyKnown(array $emojis, array $known): array
    {
        return array_values(array_unique(array_filter(
            $emojis,
            fn ($emoji): bool => is_string($emoji) && in_array($emoji, $known, true)
        )));
    }

    public function render(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('livewire.emoji-filter');
    }
}
