<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NoteLanguageTest extends TestCase
{
    use RefreshDatabase;

    private function note(User $user): Note
    {
        $note = new Note(['title' => 'Boodschappen', 'emojis' => ['🛒']]);
        $note->user_id = $user->id;
        $note->save();

        return $note;
    }

    public function test_text_the_user_wrote_is_marked_with_the_browser_language(): void
    {
        $user = User::factory()->create();
        $note = $this->note($user);
        $headers = ['Accept-Language' => 'nl-NL,nl;q=0.9,en;q=0.8'];

        $this->actingAs($user)->get(route('notes.show'), $headers)
            ->assertSee('<html lang="en"', false)
            ->assertSee('lang="nl-NL" wire:transition.navigate="note-', false);

        $this->actingAs($user)->get(route('note.show', $note->uuid), $headers)
            ->assertSee('class="note-detail-title" lang="nl-NL"', false)
            ->assertSee('id="editorjs" lang="nl-NL"', false);

        $this->actingAs($user)->get(route('note.title.show', $note->uuid), $headers)
            ->assertSee('lang="nl-NL"', false);

        $this->actingAs($user)->get(route('note.create'), $headers)
            ->assertSee('lang="nl-NL"', false);
    }

    public function test_without_a_usable_browser_language_the_app_language_is_used(): void
    {
        $user = User::factory()->create();
        $this->note($user);

        $this->actingAs($user)->get(route('notes.show'), ['Accept-Language' => '"><script>,*'])
            ->assertSee('lang="en" wire:transition.navigate="note-', false)
            ->assertDontSee('"><script>', false);
    }
}
