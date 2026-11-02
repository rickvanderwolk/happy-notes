<?php

namespace Tests\Feature;

use App\Helpers\EmojiSuggestions;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmojiSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    private function createNote(User $user, string $title, array $emojis): void
    {
        $note = new Note(['title' => $title, 'body' => '', 'emojis' => json_encode($emojis)]);
        $note->user_id = $user->id;
        $note->save();
    }

    public function test_identical_emoji_combinations_are_counted_together(): void
    {
        $user = User::factory()->create();
        Note::withoutGlobalScopes()->where('user_id', $user->id)->delete();

        $this->createNote($user, 'One', ['🏖', '☀']);
        $this->createNote($user, 'Two', ['☀', '🏖']);
        $this->createNote($user, 'Three', ['🍕']);

        $this->actingAs($user);

        $sets = collect(EmojiSuggestions::forFilter()['sets'])->sortBy('n')->values();

        $this->assertSame(1, $sets[0]['n']);
        $this->assertSame(['🍕'], $sets[0]['e']);
        $this->assertSame(2, $sets[1]['n']);
        $this->assertEqualsCanonicalizing(['🏖', '☀'], $sets[1]['e']);
    }

    public function test_a_word_links_to_the_emojis_used_more_often_with_it_than_on_average(): void
    {
        $user = User::factory()->create();

        $this->createNote($user, 'Naar het strand', ['🏖', '😊']);
        $this->createNote($user, 'Weer naar het strand', ['🏖', '😊']);
        $this->createNote($user, 'Pizza gegeten', ['🍕', '😊']);
        $this->createNote($user, 'Lekker pizza', ['🍕', '😊']);

        $this->actingAs($user);

        $words = EmojiSuggestions::forNotes()['words'];

        $this->assertSame(0.5, $words['strand']['🏖']);
        $this->assertArrayNotHasKey('🍕', $words['strand']);
        $this->assertArrayNotHasKey('😊', $words['strand']);
    }

    public function test_an_emoji_links_to_the_emojis_used_more_often_with_it_than_on_average(): void
    {
        $user = User::factory()->create();
        Note::withoutGlobalScopes()->where('user_id', $user->id)->delete();

        $this->createNote($user, 'One', ['🏖', '🍦']);
        $this->createNote($user, 'Two', ['🏖', '🍦']);
        $this->createNote($user, 'Three', ['🍕']);
        $this->createNote($user, 'Four', ['🍕']);

        $this->actingAs($user);

        $pairs = EmojiSuggestions::forNotes()['pairs'];

        $this->assertSame(0.5, $pairs['🏖']['🍦']);
        $this->assertArrayNotHasKey('🍕', $pairs);
    }

    public function test_every_entry_only_keeps_the_strongest_emojis(): void
    {
        $user = User::factory()->create();

        $this->createNote($user, 'Strand een', ['🏖', '🍦', '☀', '🐕', '🌊', '🐚', '🦀']);
        $this->createNote($user, 'Strand twee', ['🏖', '🍦', '☀', '🐕', '🌊', '🐚', '🦀']);
        $this->createNote($user, 'Pizza drie', ['🍕']);

        $this->actingAs($user);

        $this->assertCount(5, EmojiSuggestions::forNotes()['words']['strand']);
    }

    public function test_a_word_from_a_single_note_is_ignored(): void
    {
        $user = User::factory()->create();

        $this->createNote($user, 'Vulkaan gezien', ['🌋']);
        $this->createNote($user, 'Iets anders', ['🍕']);

        $this->actingAs($user);

        $this->assertArrayNotHasKey('vulkaan', EmojiSuggestions::forNotes()['words']);
    }

    public function test_only_the_notes_of_the_signed_in_user_are_used(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->createNote($owner, 'Geheim strand', ['🏖']);
        $this->createNote($owner, 'Weer strand', ['🏖']);
        $this->createNote($owner, 'Iets anders', ['🍕']);
        $this->createNote($other, 'Ander woord', ['🦊']);
        $this->createNote($other, 'Ander verhaal', ['🦊']);

        $this->actingAs($owner);

        $words = EmojiSuggestions::forNotes()['words'];

        $this->assertArrayHasKey('strand', $words);
        $this->assertArrayNotHasKey('ander', $words);
        $this->assertNotContains('🦊', collect(EmojiSuggestions::forFilter()['sets'])->pluck('e')->flatten()->all());
    }

    public function test_the_route_serves_the_right_data_and_keeps_it_off_the_disk(): void
    {
        $user = User::factory()->create();
        $this->createNote($user, 'Naar het strand', ['🏖']);

        $response = $this->actingAs($user)->getJson('/emoji-suggestions/filter?v=1');

        $response->assertOk()->assertJsonStructure(['sets']);
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->getJson('/emoji-suggestions/notes?v=1')->assertOk()->assertJsonStructure(['words', 'pairs']);
    }

    public function test_the_route_needs_a_signed_in_user_and_a_known_scope(): void
    {
        $this->getJson('/emoji-suggestions/notes')->assertUnauthorized();

        $this->actingAs(User::factory()->create())->getJson('/emoji-suggestions/unknown')->assertNotFound();
    }

    public function test_the_version_differs_per_user_and_changes_with_the_notes(): void
    {
        $first = User::factory()->create(['all_emojis' => ['🏖']]);
        $second = User::factory()->create(['all_emojis' => ['🏖']]);

        $this->actingAs($first);
        $before = EmojiSuggestions::version($first);

        $this->assertNotSame($before, EmojiSuggestions::version($second));

        $this->createNote($first, 'Nieuw', ['🏖']);

        $this->assertNotSame($before, EmojiSuggestions::version($first->fresh()));
    }
}
