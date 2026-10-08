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

    /**
     * After this many days a note counts for half in the emoji order.
     */
    private const EMOJI_RECENCY_HALF_LIFE_DAYS = 30;

    #[\Override]
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($note) {
            if (empty($note->uuid)) {
                $note->uuid = Str::uuid()->toString();
            }
        });

        // The editor autosaves the body on every typing pause. Rebuilding the emoji order
        // walks every note of the user, so only do it when the order can actually change.
        static::saved(function ($note) {
            if ($note->wasRecentlyCreated || $note->wasChanged('emojis')) {
                $note->updateUserEmojis();
            }
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
        // emojis and created_at columns are read: hydrating full models here meant
        // dragging every note body through PHP just to recount emojis.
        //
        // The order decides how emojis appear in the filter picker, so it ranks by
        // frecency: every note an emoji appears in adds a weight that fades with the
        // note's age. Emojis used often stay near the top, emojis used recently rise.
        // created_at rather than updated_at, so editing an old note does not drag its
        // emojis to the front.
        $notes = $this->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get(['emojis', 'created_at']);

        $now = now()->getTimestamp();
        $scores = [];
        foreach ($notes as $note) {
            $ageInDays = max(0, $now - ($note->created_at?->getTimestamp() ?? $now)) / 86400;
            $weight = 1 / (1 + $ageInDays / self::EMOJI_RECENCY_HALF_LIFE_DAYS);

            // Reversed so the emoji added last comes first among equal scores.
            foreach (array_reverse(array_unique($note->emojis ?? [])) as $emoji) {
                $scores[$emoji] = ($scores[$emoji] ?? 0) + $weight;
            }
        }

        // arsort() is stable, so ties keep the most recent first order built above.
        arsort($scores);

        $user->all_emojis = array_map('strval', array_keys($scores));
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
