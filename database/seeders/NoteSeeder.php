<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Note;
use Illuminate\Support\Facades\DB;

final class NoteSeeder extends Seeder
{
    public function run(): void
    {
        $emojis = ['🎉', '👋', '✨', '🫶', '😊', '✌️', '🚀', '🔥', '😘', '🦄', '🎉', '👍', '🎨', '🍀', '😻', '⭐️', '🤫', '🏖', '🌎', '🧘'];
        $users = [
          ['id' => 1, 'first_note_uuid' => 'df6da9cb-9160-4a40-ae21-f041a79bca2c'],
          ['id' => 2, 'first_note_uuid' => 'b161a8a7-82c3-4ef3-aab2-27aad88b0024'],
        ];
        $notesCount = 50;

        foreach ($users as $user) {

            for ($i = 1; $i <= $notesCount; $i++) {
                if ($i === 1) {
                    $noteUuid = $user['first_note_uuid'];
                } else {
                    $noteUuid = null;
                }
                $noteEmojis = array_rand(array_flip($emojis), rand(2, 7));
                $progress = rand(1, 7) <= 5 ? null : rand(1, 100);

                // user_id is no longer fillable, so it is set on the instance.
                // The timestamps were explicitly set to now(), which is what Eloquent
                // does by itself, so they are simply left out.
                $note = new Note();
                $note->uuid = $noteUuid;
                $note->user_id = $user['id'];
                $note->title = "Note {$i} - user {$user['id']}";
                $note->body = "Dit is de body van note {$i}.";
                $note->emojis = json_encode($noteEmojis);
                $note->progress = $progress;
                $note->save();
            }
        }
    }
}
