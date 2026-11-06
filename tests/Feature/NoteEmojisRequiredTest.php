<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoteEmojisRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_note_needs_an_emoji(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('note.store'), ['title' => 'Groceries', 'selectedEmojis' => '[]'])
            ->assertSessionHasErrors('selectedEmojis');

        $this->assertFalse(Note::withoutGlobalScopes()->where('title', 'Groceries')->exists());
    }

    public function test_an_emoji_in_the_title_is_enough(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('note.store'), ['title' => 'Groceries 🥕', 'selectedEmojis' => '[]'])
            ->assertSessionHasNoErrors();

        $this->assertSame(['🥕'], Note::withoutGlobalScopes()->where('title', 'like', 'Groceries%')->firstOrFail()->emojis);
    }

    public function test_the_emojis_of_a_note_cannot_all_be_removed(): void
    {
        $user = User::factory()->create();
        $note = new Note(['title' => 'Groceries', 'emojis' => json_encode(['🥕'])]);
        $note->user_id = $user->id;
        $note->save();

        $this->actingAs($user)
            ->post(route('note.emojis.store', $note), ['selectedEmojis' => '[]'])
            ->assertSessionHasErrors('selectedEmojis');

        $this->assertSame(['🥕'], $note->fresh()->emojis);
    }
}
