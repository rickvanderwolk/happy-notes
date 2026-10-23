<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;

/**
 * A note belongs to one user and is never shared, so every action comes down to
 * ownership. OwnNotesScope already keeps other users' notes out of every query; this
 * states the rule on the routes as well.
 */
final class NotePolicy
{
    public function view(User $user, Note $note): bool
    {
        return $this->owns($user, $note);
    }

    public function update(User $user, Note $note): bool
    {
        return $this->owns($user, $note);
    }

    public function delete(User $user, Note $note): bool
    {
        return $this->owns($user, $note);
    }

    // Cast on both sides: depending on the database driver an id can come back as a
    // string, and a strict comparison would then lock every owner out of their notes.
    private function owns(User $user, Note $note): bool
    {
        return (int) $note->user_id === (int) $user->id;
    }
}
