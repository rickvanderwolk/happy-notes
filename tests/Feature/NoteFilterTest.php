<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NoteFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param list<string> $emojis
     */
    private function noteFor(User $user, string $title, array $emojis, ?string $body = null): Note
    {
        $note = new Note(['title' => $title, 'emojis' => $emojis, 'body' => $body]);
        $note->user_id = $user->id;
        $note->save();

        return $note;
    }

    private function userWithoutWelcomeNotes(): User
    {
        $user = User::factory()->create();
        Note::withoutGlobalScopes()->where('user_id', $user->id)->delete();

        return $user;
    }

    /**
     * @param array<string, mixed> $filter
     * @return list<string>
     */
    private function listedTitles(User $user, array $filter = []): array
    {
        $user->forceFill($filter)->save();

        $notes = $this->actingAs($user)->get(route('notes.show'))->assertOk()->viewData('notes');

        return collect($notes->items())->pluck('title')->sort()->values()->all();
    }

    public function test_the_list_only_shows_notes_of_the_signed_in_user(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $other = $this->userWithoutWelcomeNotes();
        $this->noteFor($user, 'Mine', ['🍕']);
        $this->noteFor($other, 'Theirs', ['🍕']);

        $this->assertSame(['Mine'], $this->listedTitles($user));
        $this->actingAs($user)->get(route('notes.show'))->assertDontSee('Theirs');
    }

    public function test_selected_emojis_only_show_notes_carrying_all_of_them(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->noteFor($user, 'Pizza', ['🍕']);
        $this->noteFor($user, 'Pizza and burger', ['🍕', '🍔']);
        $this->noteFor($user, 'Burger', ['🍔']);

        $this->assertSame(['Pizza', 'Pizza and burger'], $this->listedTitles($user, ['selected_emojis' => ['🍕']]));
        $this->assertSame(['Pizza and burger'], $this->listedTitles($user, ['selected_emojis' => ['🍕', '🍔']]));
    }

    public function test_excluded_emojis_hide_notes_carrying_any_of_them(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->noteFor($user, 'Pizza', ['🍕']);
        $this->noteFor($user, 'Pizza and burger', ['🍕', '🍔']);
        $this->noteFor($user, 'Taco', ['🌮']);

        $this->assertSame(['Taco'], $this->listedTitles($user, ['excluded_emojis' => ['🍔', '🍕']]));
        $this->assertSame(['Pizza'], $this->listedTitles($user, ['selected_emojis' => ['🍕'], 'excluded_emojis' => ['🍔']]));
    }

    public function test_search_matches_the_title_and_the_body(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->noteFor($user, 'Shopping list', ['🛒']);
        $this->noteFor($user, 'Holiday', ['🏖️'], '{"blocks":[{"type":"paragraph","data":{"text":"Pack the shopping bag"}}]}');
        $this->noteFor($user, 'Work', ['💼']);

        $this->assertSame(['Holiday', 'Shopping list'], $this->listedTitles($user, ['search_query' => 'shopping']));
    }

    public function test_search_only_ignores_the_emoji_filter_and_search_alone_combines_with_it(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->noteFor($user, 'Pizza night', ['🍕']);
        $this->noteFor($user, 'Pizza dough', ['🥖']);

        $filter = ['search_query' => 'pizza', 'selected_emojis' => ['🍕']];

        $this->assertSame(['Pizza night'], $this->listedTitles($user, $filter + ['search_query_only' => false]));
        $this->assertSame(['Pizza dough', 'Pizza night'], $this->listedTitles($user, $filter + ['search_query_only' => true]));
    }

    public function test_storing_the_filter_keeps_only_real_emojis(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('filter.emojis.store'), [
            'storageKey' => 'selected_emojis',
            'emojis' => ['🍕', 'pizza', 42, str_repeat('🍕', 20), '🍕'],
            'otherEmojis' => ['🍔', '<b>'],
        ])->assertNoContent();

        $fresh = $user->fresh();
        $this->assertSame(['🍕'], $fresh?->selected_emojis);
        $this->assertSame(['🍔'], $fresh?->excluded_emojis);
    }

    public function test_storing_the_filter_cannot_write_any_other_user_column(): void
    {
        $user = User::factory()->create();
        $email = $user->email;

        $this->actingAs($user)->postJson(route('filter.emojis.store'), [
            'storageKey' => 'email',
            'emojis' => ['🍕'],
            'otherEmojis' => [],
        ])->assertUnprocessable();

        $this->assertSame($email, $user->fresh()?->email);
    }
}
