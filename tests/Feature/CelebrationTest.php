<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CelebrationTest extends TestCase
{
    use RefreshDatabase;

    private function createNotes(User $user, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $note = new Note(['title' => "Note {$i}", 'emojis' => '[]']);
            $note->user_id = $user->id;
            $note->save();
        }
    }

    private function storeNote(User $user): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->post(route('note.store'), ['title' => 'Another one']);
    }

    public function test_writing_the_99th_note_is_celebrated(): void
    {
        $user = User::factory()->create();
        $this->createNotes($user, 97);

        $this->storeNote($user)
            ->assertRedirect(route('notes.show'))
            ->assertSessionHas('celebration.title', '99 notes');

        $this->get(route('notes.show'))
            ->assertSee('99 notes')
            ->assertSee('Not one problem.');

        $this->assertSame(99, $user->fresh()->celebrated_milestone);
    }

    public function test_the_confetti_only_uses_emojis_the_user_picked(): void
    {
        $user = User::factory()->create();
        $this->createNotes($user, 97);

        $this->actingAs($user)->post(route('note.store'), ['title' => 'Fox 🦊'])
            ->assertSessionHas('celebration.emojis', ['🦊']);
    }

    public function test_a_note_that_is_no_milestone_is_not_celebrated(): void
    {
        $user = User::factory()->create();
        $this->createNotes($user, 98);

        $this->storeNote($user)->assertSessionMissing('celebration');
    }

    public function test_a_milestone_is_only_celebrated_once(): void
    {
        $user = User::factory()->create();
        $this->createNotes($user, 97);
        $this->storeNote($user)->assertSessionHas('celebration');

        Note::query()->latest('id')->first()->delete();

        $this->storeNote($user)->assertSessionMissing('celebration');
    }

    public function test_the_anniversary_is_celebrated_once_with_the_first_note(): void
    {
        $user = User::factory()->create(['created_at' => now()->subYear()->subDay()]);
        $this->createNotes($user, 1);

        $this->actingAs($user)->get(route('notes.show'))
            ->assertSee('1 year')
            ->assertSee('It all started with: “Note 0”', false);

        $this->assertSame(1, $user->fresh()->celebrated_anniversary);

        $this->actingAs($user->fresh())->get(route('notes.show'))
            ->assertDontSee('data-celebration', false);
    }

    public function test_no_anniversary_before_the_first_year(): void
    {
        $user = User::factory()->create(['created_at' => now()->subMonths(11)]);

        $this->actingAs($user)->get(route('notes.show'))
            ->assertDontSee('data-celebration', false);
    }
}
