<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'all_emojis',
        'selected_emojis',
        'excluded_emojis',
        'search_query',
        'search_query_only',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'selected_emojis' => 'array',
            'excluded_emojis' => 'array',
            'all_emojis' => 'array',
            'search_query_only' => 'boolean',
        ];
    }

    #[\Override]
    protected static function booted()
    {
        static::created(function ($user) {
            $appName = Config::get('app.name');
            // Ignore for now, might fix in the future
            // phpcs:ignore Generic.Files.LineLength.TooLong
            $body = '{"time":1739737330842,"blocks":[{"id":"HIhquOBp1y","type":"header","data":{"text":"Hi!&nbsp;👋","level":2}},{"id":"rbrUZDvm-F","type":"paragraph","data":{"text":"Happy to see you here!"}},{"id":"6beAi2OFBH","type":"header","data":{"text":"Enrich your notes&nbsp;✨","level":2}},{"id":"MDDBt68Iue","type":"paragraph","data":{"text":"Aside form normal text you can add things like:&nbsp;"}},{"id":"batFKBxeM1","type":"list","data":{"style":"unordered","meta":[],"items":[{"content":"A list of one,","meta":[],"items":[]},{"content":"two","meta":[],"items":[]},{"content":"or even more items","meta":[],"items":[]}]}},{"id":"722Ezue7R_","type":"checklist","data":{"items":[{"text":"A checklist with both checked","checked":true},{"text":"and unchecked items","checked":false}]}},{"id":"nyU-TM_I6A","type":"paragraph","data":{"text":"A table&nbsp;"}},{"id":"xvnx41hXEH","type":"table","data":{"withHeadings":false,"stretched":false,"content":[["My column 1","My column 2"],["Abc","Xyz"]]}},{"id":"r5qPdo94sF","type":"header","data":{"text":"Thank you! 🫶","level":2}},{"id":"oapNJXQZLe","type":"paragraph","data":{"text":"Thank you for trying ' . $appName . '!"}}],"version":"2.31.0-rc.7"}';
            Note::create([
                'user_id' => $user->id,
                'title' => "Welcome to $appName!",
                'body' => $body,
                'emojis' => json_encode(['🎉', '👋', '✨', '🫶'], JSON_UNESCAPED_UNICODE),
                'progress' => 50,
            ]);
        });
    }
}
