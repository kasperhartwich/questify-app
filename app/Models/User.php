<?php

namespace App\Models;

use App\Models\Concerns\HasImageUrls;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasImageUrls, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_path',
        'locale',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function quests(): HasMany
    {
        return $this->hasMany(Quest::class, 'creator_id');
    }

    public function hostedSessions(): HasMany
    {
        return $this->hasMany(QuestSession::class, 'host_id');
    }

    public function sessionParticipations(): HasMany
    {
        return $this->hasMany(SessionParticipant::class);
    }

    public function questRatings(): HasMany
    {
        return $this->hasMany(QuestRating::class);
    }

    public function reportedFlags(): HasMany
    {
        return $this->hasMany(ModerationFlag::class, 'reporter_id');
    }

    public function moderatedFlags(): HasMany
    {
        return $this->hasMany(ModerationFlag::class, 'moderator_id');
    }

    public function favouriteQuests(): BelongsToMany
    {
        return $this->belongsToMany(Quest::class, 'quest_favourites')->withTimestamps();
    }
}
