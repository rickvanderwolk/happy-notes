<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use App\Scopes\OwnNotesScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class NoteOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function noteFor(User $user, string $title): Note
    {
        $note = new Note(['title' => $title, 'emojis' => '[]']);
        $note->user_id = $user->id;
        $note->save();

        return $note;
    }

    public function test_queries_find_no_notes_without_a_signed_in_user(): void
    {
        $owner = User::factory()->create();
        $this->noteFor($owner, 'Private');

        $this->assertSame(0, Note::query()->count());
        $this->assertGreaterThan(0, Note::withoutGlobalScope(OwnNotesScope::class)->count());
    }

    public function test_a_signed_in_user_only_sees_their_own_notes(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->noteFor($owner, 'Mine');
        $this->noteFor($other, 'Theirs');

        $this->actingAs($owner);

        $titles = Note::query()->pluck('title');
        $this->assertContains('Mine', $titles);
        $this->assertNotContains('Theirs', $titles);
    }

    public function test_the_owner_can_open_their_note(): void
    {
        $owner = User::factory()->create();
        $note = $this->noteFor($owner, 'Mine');

        $this->actingAs($owner)->get(route('note.show', $note))->assertOk();
    }

    public function test_another_user_cannot_open_or_change_a_note(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $note = $this->noteFor($owner, 'Mine');

        $this->actingAs($other)->get(route('note.show', $note->uuid))->assertNotFound();
        $this->actingAs($other)->post(route('note.title.store', $note->uuid), ['title' => 'Taken'])->assertNotFound();
        $this->actingAs($other)->delete(route('note.destroy', $note->uuid))->assertNotFound();

        $this->assertSame('Mine', Note::withoutGlobalScope(OwnNotesScope::class)->find($note->id)?->title);
    }

    public function test_the_policy_only_allows_the_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $note = $this->noteFor($owner, 'Mine');

        foreach (['view', 'update', 'delete'] as $ability) {
            $this->assertTrue(Gate::forUser($owner)->allows($ability, $note));
            $this->assertTrue(Gate::forUser($other)->denies($ability, $note));
        }
    }

    // The welcome note is saved before anyone is signed in, so the emoji list it feeds
    // must not depend on the scope.
    public function test_a_new_user_gets_the_emojis_of_their_welcome_note(): void
    {
        $user = User::factory()->create();

        $this->assertNotEmpty($user->fresh()?->all_emojis);
    }
}
