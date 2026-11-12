<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteDoubleSubmitTest extends TestCase
{
    use RefreshDatabase;

    private function store(User $user, string $title, string $emojis = '["🥕"]'): void
    {
        $this->actingAs($user)
            ->post(route('note.store'), ['title' => $title, 'selectedEmojis' => $emojis])
            ->assertRedirect(route('notes.show'));
    }

    public function test_saving_the_same_new_note_twice_keeps_one(): void
    {
        $user = User::factory()->create();

        $this->store($user, 'Groceries');
        $this->store($user, 'Groceries');

        $this->assertSame(1, Note::withoutGlobalScopes()->where('user_id', $user->id)->whereIn('title', ['Groceries', 'Laundry'])->count());
    }

    public function test_a_different_note_is_still_saved(): void
    {
        $user = User::factory()->create();

        $this->store($user, 'Groceries');
        $this->store($user, 'Groceries', '["🍎"]');
        $this->store($user, 'Laundry');

        $this->assertSame(3, Note::withoutGlobalScopes()->where('user_id', $user->id)->whereIn('title', ['Groceries', 'Laundry'])->count());
    }

    public function test_the_same_note_later_is_a_new_note(): void
    {
        $user = User::factory()->create();

        $this->store($user, 'Groceries');
        $this->travel(11)->seconds();
        $this->store($user, 'Groceries');

        $this->assertSame(2, Note::withoutGlobalScopes()->where('user_id', $user->id)->whereIn('title', ['Groceries', 'Laundry'])->count());
    }

    public function test_another_user_can_save_the_same_note(): void
    {
        $this->store(User::factory()->create(), 'Groceries');
        $this->store(User::factory()->create(), 'Groceries');

        $this->assertSame(2, Note::withoutGlobalScopes()->where('title', 'Groceries')->count());
    }
}
