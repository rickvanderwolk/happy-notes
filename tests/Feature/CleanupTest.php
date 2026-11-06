<?php

namespace Tests\Feature;

use App\Livewire\Cleanup;
use App\Models\Note;
use App\Models\User;
use App\Scopes\OwnNotesScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class CleanupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param list<string> $emojis
     */
    private function noteFor(User $user, array $emojis): Note
    {
        $note = new Note(['title' => 'Note', 'emojis' => $emojis]);
        $note->user_id = $user->id;
        $note->save();

        return $note;
    }

    /**
     * @return list<string>|null
     */
    private function emojisOf(Note $note): ?array
    {
        return Note::withoutGlobalScope(OwnNotesScope::class)->find($note->id)?->emojis;
    }

    public function test_remove_emoji_takes_it_out_of_notes_with_more_than_one_emoji(): void
    {
        $user = User::factory()->create();
        $first = $this->noteFor($user, ['🍕', '🍔']);
        $second = $this->noteFor($user, ['🌮', '🍕', '🥗']);

        Livewire::actingAs($user)->test(Cleanup::class)->call('removeEmoji', '🍕');

        $this->assertSame(['🍔'], $this->emojisOf($first));
        $this->assertSame(['🌮', '🥗'], $this->emojisOf($second));
    }

    public function test_a_note_with_only_that_emoji_keeps_it(): void
    {
        $user = User::factory()->create();
        $only = $this->noteFor($user, ['🍕']);
        $more = $this->noteFor($user, ['🍕', '🍔']);

        Livewire::actingAs($user)->test(Cleanup::class)->call('removeEmoji', '🍕');

        $this->assertSame(['🍕'], $this->emojisOf($only));
        $this->assertSame(['🍔'], $this->emojisOf($more));
    }

    public function test_notes_of_other_users_are_left_alone(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->noteFor($user, ['🍕', '🍔']);
        $theirs = $this->noteFor($other, ['🍕', '🍔']);

        Livewire::actingAs($user)->test(Cleanup::class)->call('removeEmoji', '🍕');

        $this->assertSame(['🍕', '🍔'], $this->emojisOf($theirs));
        $this->assertContains('🍕', $other->fresh()?->all_emojis ?? []);
    }

    public function test_all_emojis_is_updated_after_removing(): void
    {
        $user = User::factory()->create();
        $this->noteFor($user, ['🍕', '🍔']);
        $this->noteFor($user, ['🍕', '🌮']);
        $this->assertContains('🍕', $user->fresh()?->all_emojis ?? []);

        Livewire::actingAs($user)->test(Cleanup::class)->call('removeEmoji', '🍕');

        $allEmojis = $user->fresh()?->all_emojis ?? [];
        $this->assertNotContains('🍕', $allEmojis);
        $this->assertContains('🍔', $allEmojis);
        $this->assertContains('🌮', $allEmojis);
    }

    public function test_emoji_stats_count_notes_and_know_which_emojis_can_be_removed(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->noteFor($user, ['🍕', '🍔']);
        $this->noteFor($user, ['🍕']);
        $this->noteFor($user, ['🍔', '🌮']);
        $this->noteFor($other, ['🍕', '🌮']);

        $stats = Livewire::actingAs($user)->test(Cleanup::class)->get('emojiStats');

        $this->assertSame(['count' => 2, 'canRemove' => false], $stats['🍕']);
        $this->assertSame(['count' => 2, 'canRemove' => true], $stats['🍔']);
        $this->assertSame(['count' => 1, 'canRemove' => true], $stats['🌮']);
        $this->assertSame(['count' => 1, 'canRemove' => true], $stats['🎉']);
    }

    public function test_emoji_stats_are_refreshed_after_removing(): void
    {
        $user = User::factory()->create();
        $this->noteFor($user, ['🍕', '🍔']);

        $component = Livewire::actingAs($user)->test(Cleanup::class);
        $this->assertArrayHasKey('🍕', $component->get('emojiStats'));

        $component->call('removeEmoji', '🍕');

        $this->assertArrayNotHasKey('🍕', $component->get('emojiStats'));
        $this->assertSame(['count' => 1, 'canRemove' => false], $component->get('emojiStats')['🍔']);
    }

    public function test_filter_by_emoji_selects_only_that_emoji_and_opens_the_notes(): void
    {
        $user = User::factory()->create();
        $user->selected_emojis = ['🍔', '🌮'];
        $user->excluded_emojis = ['🥗'];
        $user->save();

        Livewire::actingAs($user)->test(Cleanup::class)
            ->call('filterByEmoji', '🍕')
            ->assertRedirect(route('notes.show'));

        $fresh = $user->fresh();
        $this->assertSame(['🍕'], $fresh?->selected_emojis);
        $this->assertSame([], $fresh?->excluded_emojis);
    }
}
