<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NoteListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<Note> newest first, the order the list shows them in
     */
    private function createNotes(User $user, int $count): array
    {
        $notes = [];
        for ($i = 0; $i < $count; $i++) {
            $note = new Note(['title' => "Note {$i}", 'emojis' => '[]']);
            $note->user_id = $user->id;
            $note->updated_at = now()->addMinutes($i);
            $note->save();
            array_unshift($notes, $note);
        }

        return $notes;
    }

    private function userWithoutWelcomeNotes(): User
    {
        $user = User::factory()->create();
        Note::withoutGlobalScopes()->where('user_id', $user->id)->delete();

        return $user;
    }

    public function test_loading_up_to_a_note_returns_every_page_until_it_in_one_request(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $notes = $this->createNotes($user, 50);
        $target = $notes[35];

        $response = $this->actingAs($user)
            ->get(route('notes.show', ['page' => 2, 'partial' => 1, 'until' => $target->uuid]));

        $response->assertOk()->assertHeader('X-Last-Page', '3');
        $response->assertSee("note-{$notes[15]->uuid}");
        $response->assertSee("note-{$target->uuid}");
        $response->assertSee("note-{$notes[44]->uuid}");
        $response->assertDontSee("note-{$notes[14]->uuid}");
        $response->assertDontSee("note-{$notes[45]->uuid}");
    }

    public function test_loading_up_to_a_note_that_is_not_in_the_list_returns_nothing(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->createNotes($user, 20);

        $response = $this->actingAs($user)
            ->get(route('notes.show', ['page' => 2, 'partial' => 1, 'until' => 'not-a-note']));

        $response->assertOk()->assertHeader('X-Last-Page', '1');
        $response->assertDontSee('note-card');
    }

    public function test_autosave_returns_the_progress(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        [$note] = $this->createNotes($user, 1);

        $body = ['blocks' => [['type' => 'checklist', 'data' => ['items' => [
            ['text' => 'a', 'checked' => true],
            ['text' => 'b', 'checked' => false],
        ]]]]];

        $this->actingAs($user)
            ->postJson(route('note.body.store', $note->uuid), ['body' => $body])
            ->assertOk()
            ->assertJson(['progress' => $note->fresh()->progress]);
    }

    public function test_saving_a_note_without_emoji_changes_leaves_the_emoji_order_alone(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        [$note] = $this->createNotes($user, 1);
        $note = $note->fresh();

        DB::enableQueryLog();
        $note->title = 'Changed';
        $note->save();

        $userQueries = array_filter(DB::getQueryLog(), fn ($q) => str_contains($q['query'], '"users"'));
        $this->assertSame([], array_values($userQueries));
    }

    public function test_saving_a_note_with_new_emojis_updates_the_emoji_order(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        [$note] = $this->createNotes($user, 1);

        $note->emojis = json_encode(['🦊']);
        $note->save();

        $this->assertContains('🦊', $user->fresh()->all_emojis);
    }
}
