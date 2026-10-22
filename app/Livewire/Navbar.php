<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class Navbar extends Component
{
    /** @var list<string> */
    public array $selectedEmojis = [];
    /** @var list<string> */
    public array $excludedEmojis = [];
    public ?string $searchQuery = null;
    public ?string $originalRoute = null;

    /** @var array<string, string> */
    protected $listeners = ['filterUpdated' => 'updateFilter'];

    public function mount(): void
    {
        $this->originalRoute = session('original_route_name', request()->route()?->getName());
        $this->updateFilter();
    }

    public function updateFilter(): void
    {
        $user = Auth::user();
        $this->selectedEmojis = $user->selected_emojis ?? [];
        $this->excludedEmojis = $user->excluded_emojis ?? [];
        $this->searchQuery = $user->search_query ?? null;
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
