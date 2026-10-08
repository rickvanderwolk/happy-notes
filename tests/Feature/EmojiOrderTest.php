<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmojiOrderTest extends TestCase
{
    use RefreshDatabase;

    private function createNote(User $user, array $emojis, int $daysAgo): Note
    {
        $note = new Note(['title' => 'Note', 'body' => '', 'emojis' => json_encode($emojis)]);
        $note->user_id = $user->id;
        $note->created_at = now()->subDays($daysAgo);
        $note->save();

        return $note;
    }

    /**
     * New users start with welcome notes, so only the relative order of the given emojis
     * is compared.
     */
    private function orderOf(array $emojis, User $user): array
    {
        return array_values(array_intersect($user->fresh()->all_emojis, $emojis));
    }

    public function test_emojis_used_often_rank_above_emojis_used_once_recently(): void
    {
        $user = User::factory()->create();

        $this->createNote($user, ['🍕'], 10);
        $this->createNote($user, ['🍕'], 12);
        $this->createNote($user, ['🍕'], 14);
        $this->createNote($user, ['🎉'], 0);

        $this->assertSame(['🍕', '🎉'], $this->orderOf(['🍕', '🎉'], $user));
    }

    public function test_recent_emojis_rank_above_equally_used_old_emojis(): void
    {
        $user = User::factory()->create();

        $this->createNote($user, ['🦕'], 300);
        $this->createNote($user, ['🦕'], 310);
        $this->createNote($user, ['🚀'], 1);
        $this->createNote($user, ['🚀'], 2);

        $this->assertSame(['🚀', '🦕'], $this->orderOf(['🚀', '🦕'], $user));
    }

    public function test_editing_an_old_note_does_not_move_its_emojis_to_the_front(): void
    {
        $user = User::factory()->create();

        $old = $this->createNote($user, ['🦕'], 300);
        $this->createNote($user, ['🚀'], 1);

        $old->title = 'Edited';
        $old->save();

        $this->assertSame(['🚀', '🦕'], $this->orderOf(['🚀', '🦕'], $user));
    }
}
