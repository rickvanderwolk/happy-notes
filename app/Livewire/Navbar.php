<?php

namespace app\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class Navbar extends Component
{
    public $selectedEmojis = [];
    public $excludedEmojis = [];
    public $searchQuery = null;
    public $originalRoute = null;

    protected $listeners = ['filterUpdated' => 'updateFilter'];

    public function mount(): void
    {
        $this->originalRoute = session('original_route_name', request()->route()->getName());
        $this->updateFilter();
    }

    public function updateFilter(): void
    {
        $user = Auth::user();
        $selectedEmojis = $user->selected_emojis ?? [];
        $excludedEmojis = $user->excluded_emojis ?? [];
        $searchQuery = $user->search_query ?? null;

        /** @psalm-suppress NoValue */
        if (is_string($selectedEmojis)) {
            $selectedEmojis = json_decode($selectedEmojis, true) ?? [];
        }
        /** @psalm-suppress NoValue */
        if (is_string($excludedEmojis)) {
            $excludedEmojis = json_decode($excludedEmojis, true) ?? [];
        }

        $this->selectedEmojis = $selectedEmojis;
        $this->excludedEmojis = $excludedEmojis;
        $this->searchQuery = $searchQuery;
        $this->render();
    }

    public function render(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('livewire.navbar', [
            'selectedEmojis' => $this->selectedEmojis,
            'excludedEmojis' => $this->excludedEmojis,
            'searchQuery' => $this->searchQuery,
        ]);
    }
}
