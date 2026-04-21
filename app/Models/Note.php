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
    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'body',
        'emojis',
        'created_at',
        'updated_at',
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

        if ($user) {
            $allNotes = $this->where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->get();
            $allEmojis = [];

            foreach ($allNotes as $note) {
                $noteEmojis = $note->emojis ?? [];
                $noteEmojis = array_reverse($noteEmojis);
                $allEmojis = array_merge($allEmojis, $noteEmojis);
            }

            $user->all_emojis = array_values(array_unique($allEmojis));
            $user->save();
        }
    }

    /**
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getEmojisAttribute($value)
    {
        return json_decode($value, true);
    }
}
