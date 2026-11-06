<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_search_does_not_match_the_json_or_html_around_the_text(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->noteFor($user, 'Formatted', ['📝'], '{"time":1,"blocks":[{"id":"a","type":"paragraph","data":{"text":"Some <b>bold</b>&nbsp;words"}},{"id":"b","type":"checklist","data":{"items":[{"text":"Milk","checked":true}]}}],"version":"2.31.0"}');

        foreach (['blocks', 'paragraph', 'text', 'type', 'data', 'checked', 'items', 'version', 'time', 'nbsp', '<b>', 'true'] as $term) {
            $this->assertSame([], $this->listedTitles($user, ['search_query' => $term]), $term);
        }

        $this->assertSame(['Formatted'], $this->listedTitles($user, ['search_query' => 'bold words']));
        $this->assertSame(['Formatted'], $this->listedTitles($user, ['search_query' => 'milk']));
    }

    public function test_search_finds_text_in_every_kind_of_block(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->noteFor($user, 'Header', ['📝'], '{"blocks":[{"type":"header","data":{"text":"Findme header","level":2}}]}');
        $this->noteFor($user, 'List', ['📝'], '{"blocks":[{"type":"list","data":{"style":"unordered","meta":[],"items":[{"content":"Top","meta":[],"items":[{"content":"Findme nested","meta":[],"items":[]}]}]}}]}');
        $this->noteFor($user, 'Checklist', ['📝'], '{"blocks":[{"type":"checklist","data":{"items":[{"text":"Findme todo","checked":false}]}}]}');
        $this->noteFor($user, 'Table', ['📝'], '{"blocks":[{"type":"table","data":{"withHeadings":false,"content":[["a","Findme cell"]]}}]}');
        $this->noteFor($user, 'Code', ['📝'], '{"blocks":[{"type":"code","data":{"code":"findme()"}}]}');
        $this->noteFor($user, 'Other', ['📝'], '{"blocks":[{"type":"paragraph","data":{"text":"Nothing here"}}]}');

        $this->assertSame(['Checklist', 'Code', 'Header', 'List', 'Table'], $this->listedTitles($user, ['search_query' => 'findme']));
    }

    public function test_percent_and_underscore_in_a_search_are_taken_literally(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $this->noteFor($user, '100% done', ['✅']);
        $this->noteFor($user, 'snake_case', ['🐍']);
        $this->noteFor($user, 'snakeXcase', ['🐍']);
        $this->noteFor($user, 'Wow! Great', ['🎉']);

        $this->assertSame(['100% done'], $this->listedTitles($user, ['search_query' => '%']));
        $this->assertSame(['snake_case'], $this->listedTitles($user, ['search_query' => 'snake_case']));
        $this->assertSame(['Wow! Great'], $this->listedTitles($user, ['search_query' => 'wow!']));
    }

    public function test_saving_the_body_from_the_editor_makes_it_searchable(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $note = $this->noteFor($user, 'Edited', ['📝']);

        $this->actingAs($user)->postJson(route('note.body.store', $note->uuid), [
            'body' => ['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Freshly typed']]]],
        ])->assertOk();

        $this->assertSame(['Edited'], $this->listedTitles($user, ['search_query' => 'freshly']));

        $this->actingAs($user)->postJson(route('note.body.store', $note->uuid), ['body' => null])->assertOk();

        $this->assertSame([], $this->listedTitles($user, ['search_query' => 'freshly']));
    }

    public function test_the_migration_fills_the_text_of_existing_notes(): void
    {
        $user = $this->userWithoutWelcomeNotes();
        $note = $this->noteFor($user, 'Existing', ['📝'], '{"blocks":[{"type":"paragraph","data":{"text":"From <i>before</i> the column"}}]}');
        $empty = $this->noteFor($user, 'Empty', ['📝']);
        DB::table('notes')->update(['body_text' => null]);

        $migration = require database_path('migrations/2026_11_06_000000_add_body_text_to_notes_table.php');
        $migration->up();

        $this->assertSame('From before the column', DB::table('notes')->where('id', $note->id)->value('body_text'));
        $this->assertNull(DB::table('notes')->where('id', $empty->id)->value('body_text'));
        $this->assertSame(['Existing'], $this->listedTitles($user, ['search_query' => 'before']));
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
