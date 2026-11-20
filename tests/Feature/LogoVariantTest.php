<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoVariantTest extends TestCase
{
    use RefreshDatabase;

    private function userWithNotes(array $emojiSets): User
    {
        $user = User::factory()->create();
        Note::withoutGlobalScopes()->where('user_id', $user->id)->delete();

        foreach ($emojiSets as $emojis) {
            $note = new Note(['title' => 'Note', 'emojis' => json_encode($emojis)]);
            $note->user_id = $user->id;
            $note->save();
        }

        return $user;
    }

    public function test_an_ordinary_day_has_no_variant(): void
    {
        $this->travelTo('2026-03-31 12:00');

        $this->get('/')->assertDontSee('data-logo-variant', false);
    }

    public function test_the_logo_stands_on_its_head_on_april_fools_day(): void
    {
        $this->travelTo('2027-04-01 12:00');

        $this->get('/')->assertSee('data-logo-variant="april-fools"', false);
    }

    public function test_the_logo_turns_into_the_users_most_used_emojis_on_emoji_day(): void
    {
        $this->travelTo('2027-07-17 12:00');
        $this->actingAs($this->userWithNotes([['🍕', '☕'], ['☕'], ['☕', '🌱']]));

        $response = $this->get(route('menu.show'));

        $response->assertSee('data-logo-variant="emoji-day"', false);
        $response->assertSee('data-logo-emojis="[&quot;☕&quot;', false);
        $response->assertSee('🍕');
        $response->assertSee('🌱');
    }

    public function test_without_emojis_of_their_own_the_logo_uses_the_welcome_note_emojis(): void
    {
        $this->travelTo('2027-07-17 12:00');
        $this->actingAs($this->userWithNotes([]));

        $this->get(route('menu.show'))->assertSee(
            'data-logo-emojis="' . e(json_encode(User::WELCOME_EMOJIS, JSON_UNESCAPED_UNICODE)) . '"',
            false
        );
    }
}
