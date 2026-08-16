<?php

namespace App\Models;

use App\Scopes\OwnNotesScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class Note extends Model
{
    use HasFactory;

    protected $table = 'notes';
    protected $primaryKey = 'id';
    public $incrementing = true;
    /**
     * Deliberately without 'user_id' and the timestamps: those decide who owns a note and
     * when it was written, and neither should ever be settable from request data. Set them
     * on the instance instead, as NoteController does.
     */
    protected $fillable = [
        'uuid',
        'title',
        'body',
        'emojis',
        'progress',
    ];
    public $timestamps = true;

    #[\Override]
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($note) {
            if (empty($note->uuid)) {
                $note->uuid = Str::uuid()->toString();
            }
        });

        static::saved(function ($note) {
            $note->updateUserEmojis();
        });

        static::deleted(function ($note) {
            $note->updateUserEmojis();
        });
    }

    #[\Override]
    protected static function booted()
    {
        static::addGlobalScope(new OwnNotesScope());
    }

    #[\Override]
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function updateUserEmojis(): void
    {
        $userId = $this->user_id;
        $user = User::find($userId);

        if (!$user) {
            return;
        }

        // This runs on every save and every delete, so it must stay cheap. Only the
        // emojis column is read: hydrating full models here meant dragging every note
        // body through PHP just to recount emojis. Ordering is unchanged, because it
        // decides the order emojis appear in the filter picker.
        // pluck() runs the values through getEmojisAttribute(), so these are decoded
        // arrays rather than the raw JSON strings the analyser assumes.
        /** @var iterable<int, list<string>|null> $emojisPerNote */
        $emojisPerNote = $this->where('user_id', $userId)
            ->orderBy('updated_at', 'desc')
            ->pluck('emojis');

        $allEmojis = [];
        foreach ($emojisPerNote as $noteEmojis) {
            $allEmojis = array_merge($allEmojis, array_reverse($noteEmojis ?? []));
        }

        $user->all_emojis = array_values(array_unique($allEmojis));
        $user->save();
    }

    /**
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function getEmojisAttribute($value)
    {
        return json_decode($value, true);
    }
}
