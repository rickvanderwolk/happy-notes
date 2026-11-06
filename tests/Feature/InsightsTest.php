<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Note::withoutGlobalScopes()->where('user_id', $this->user->id)->delete();
    }

    private function createNotes(int $times, array $emojis, int $daysAgo): void
    {
        for ($i = 0; $i < $times; $i++) {
            $note = new Note(['title' => 'Note', 'body' => '', 'emojis' => json_encode($emojis)]);
            $note->user_id = $this->user->id;
            $note->created_at = now()->subDays($daysAgo);
            $note->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function period(string $key, ?string $timezone = null): array
    {
        if ($timezone !== null) {
            $this->withUnencryptedCookie('tz', $timezone);
        }

        $response = $this->actingAs($this->user)->get('/insights');
        $response->assertOk();

        return collect($response->viewData('periods'))->firstWhere('key', $key);
    }

    public function test_often_together_ranks_pairs_by_count(): void
    {
        $this->createNotes(10, ['🧪', '🎨', '👽'], 5);
        $this->createNotes(10, ['🧪', '🍂'], 5);

        $combos = $this->period('month')['emojiCombos'];

        $this->assertSame(10, $combos[0]['count']);
        $this->assertCount(4, $combos);
        $this->assertContains('🎨 + 🧪', array_column($combos, 'label'));
    }

    public function test_three_months_is_compared_with_the_year_before_it(): void
    {
        $this->createNotes(20, ['🌈', '🧪'], 200);
        $this->createNotes(20, ['👽', '🧪'], 30);

        $shifts = $this->period('quarter')['emojiShifts'];

        $this->assertSame('👽', $shifts['rising'][0]['emoji']);
        $this->assertSame(0, $shifts['rising'][0]['from']);
        $this->assertSame(100, $shifts['rising'][0]['to']);
        $this->assertSame('🌈', $shifts['fading'][0]['emoji']);
        $this->assertNotContains('🧪', array_column([...$shifts['rising'], ...$shifts['fading']], 'emoji'));
    }

    public function test_only_three_months_has_rising_and_fading(): void
    {
        $this->createNotes(20, ['🌈'], 200);
        $this->createNotes(20, ['👽'], 10);

        $this->assertNotSame([], $this->period('quarter')['emojiShifts']['rising']);
        $this->assertSame(['rising' => [], 'fading' => []], $this->period('month')['emojiShifts']);
        $this->assertSame(['rising' => [], 'fading' => []], $this->period('all')['emojiShifts']);
    }

    public function test_no_shifts_without_enough_history(): void
    {
        $this->createNotes(10, ['👽'], 5);
        $this->createNotes(5, ['🌈'], 200);

        $this->assertSame(['rising' => [], 'fading' => []], $this->period('quarter')['emojiShifts']);
    }

    public function test_days_and_dayparts_follow_the_browser_timezone(): void
    {
        $this->travelTo(now()->parse('2026-01-15 12:00:00'));

        $note = new Note(['title' => 'Note', 'body' => '', 'emojis' => json_encode(['🌙'])]);
        $note->user_id = $this->user->id;
        $note->created_at = now()->parse('2026-01-14 23:30:00');
        $note->save();

        $utc = $this->period('all');
        $this->assertSame(1, collect($utc['weekdays']['list'])->firstWhere('label', 'Wed')['count']);
        $this->assertSame(1, collect($utc['dayparts']['list'])->firstWhere('label', 'Evening')['count']);

        $local = $this->period('all', 'Europe/Amsterdam');
        $this->assertSame(1, collect($local['weekdays']['list'])->firstWhere('label', 'Thu')['count']);
        $this->assertSame(1, collect($local['dayparts']['list'])->firstWhere('label', 'Night')['count']);
    }

    public function test_an_invalid_timezone_falls_back_to_the_app_timezone(): void
    {
        $this->travelTo(now()->parse('2026-01-15 12:00:00'));

        $note = new Note(['title' => 'Note', 'body' => '', 'emojis' => json_encode(['🌙'])]);
        $note->user_id = $this->user->id;
        $note->created_at = now()->parse('2026-01-14 23:30:00');
        $note->save();

        $period = $this->period('all', 'Mars/Olympus');

        $this->assertSame(1, collect($period['dayparts']['list'])->firstWhere('label', 'Evening')['count']);
    }

    public function test_the_note_says_before_when_there_is_less_than_a_year_of_history(): void
    {
        $this->createNotes(20, ['🌈'], 200);
        $this->createNotes(20, ['👽'], 30);

        $this->assertSame('vs before', $this->period('quarter')['shiftNote']);

        $this->createNotes(1, ['🌈'], 600);

        $this->assertSame('vs the year before', $this->period('quarter')['shiftNote']);
    }
}
