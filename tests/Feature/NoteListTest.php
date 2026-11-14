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

    /**
     * @return list<string>
     */
    private function uuidsIn(string $html): array
    {
        preg_match_all('/id="note-([0-9a-f-]{36})"/', $html, $matches);

        return $matches[1];
    }

    private function shuffleFor(User $user, ?int $seed): void
    {
        $user->shuffle_seed = $seed;
        $user->save();
    }

    public function test_shuffle_stores_a_seed_and_returns_to_the_list(): void
    {
        $user = $this->userWithoutWelcomeNotes();

        $this->actingAs($user)
            ->get(route('notes.shuffle'))
            ->assertRedirect(route('notes.show'));

        $this->assertNotNull($user->fresh()->shuffle_seed);
    }

    public function test_a_shuffle_follows_you_to_another_device(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->createNotes($user, 3);

        $this->actingAs($user)->get(route('notes.shuffle'));
        $this->flushSession();

        $this->actingAs($user->fresh())->get(route('notes.show'))
            ->assertSee('data-cy="shuffle-label"', false);
    }

    /**
     * The list as infinite scroll builds it: the first page with the order kept with the user,
     * then every further batch asking for the order the list was opened with.
     *
     * @return list<string>
     */
    private function scrollThrough(User $user, int $seed, int $pages, ?int $seedFromSecondPage = null): array
    {
        $this->shuffleFor($user, $seed);
        $first = $this->actingAs($user)->get(route('notes.show'));
        preg_match('/data-shuffle="(\d+)"/', $first->getContent(), $match);
        $uuids = $this->uuidsIn($first->getContent());

        $this->shuffleFor($user, $seedFromSecondPage ?? $seed);
        for ($page = 2; $page <= $pages; $page++) {
            $batch = $this->actingAs($user)
                ->get(route('notes.show', ['page' => $page, 'partial' => 1, 'shuffle' => $match[1]]));
            $uuids = [...$uuids, ...$this->uuidsIn($batch->getContent())];
        }

        return $uuids;
    }

    public function test_scrolling_a_shuffled_list_shows_every_note_once_in_an_order_that_stays_the_same(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $notes = $this->createNotes($user, 50);
        $recent = array_map(fn (Note $note) => $note->uuid, $notes);

        $shuffled = $this->scrollThrough($user, 123456789, 4);

        $this->assertCount(50, $shuffled);
        $this->assertSame($shuffled, array_values(array_unique($shuffled)));
        $this->assertEqualsCanonicalizing($recent, $shuffled);
        $this->assertNotSame($recent, $shuffled);
        $this->assertSame($shuffled, $this->scrollThrough($user, 123456789, 4));
    }

    public function test_shuffling_on_another_device_does_not_change_a_list_halfway(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $notes = $this->createNotes($user, 50);
        $recent = array_map(fn (Note $note) => $note->uuid, $notes);

        $this->assertEqualsCanonicalizing($recent, $this->scrollThrough($user, 123456789, 4, 987654321));
        $this->assertSame($this->scrollThrough($user, 123456789, 4), $this->scrollThrough($user, 123456789, 4, 987654321));
    }

    public function test_a_list_opened_in_recent_order_stays_in_it_when_another_device_shuffles(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $notes = $this->createNotes($user, 30);
        $recent = array_map(fn (Note $note) => $note->uuid, $notes);
        $this->shuffleFor($user, 42);

        $batch = $this->actingAs($user)
            ->get(route('notes.show', ['page' => 2, 'partial' => 1, 'shuffle' => 0]));

        $this->assertSame(array_slice($recent, 15, 15), $this->uuidsIn($batch->getContent()));
    }

    public function test_a_shuffled_list_shows_the_label_to_stop_shuffling(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->createNotes($user, 3);

        $this->actingAs($user)->get(route('notes.show'))
            ->assertDontSee('data-cy="shuffle-label"', false);

        $this->shuffleFor($user, 42);
        $this->actingAs($user)->get(route('notes.show'))
            ->assertSee('data-cy="shuffle-label"', false)
            ->assertSee(route('notes.unshuffle'));
    }

    public function test_unshuffle_brings_back_the_most_recent_first(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->shuffleFor($user, 42);

        $this->actingAs($user)
            ->get(route('notes.unshuffle'))
            ->assertRedirect(route('notes.show'));

        $this->assertNull($user->fresh()->shuffle_seed);
    }

    public function test_saving_a_new_note_stops_shuffling(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->shuffleFor($user, 42);

        $this->actingAs($user)
            ->post(route('note.store'), ['title' => 'New 🦊'])
            ->assertRedirect(route('notes.show'));

        $this->assertNull($user->fresh()->shuffle_seed);
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
