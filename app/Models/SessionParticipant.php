<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SessionParticipant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quest_session_id',
        'user_id',
        'display_name',
        'current_checkpoint_index',
        'score',
        'finished_at',
    ];

    /** @var list<string> */
    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'finished_at' => 'datetime',
        ];
    }

    /**
     * Give the participant a fresh token (saved with the model) and return it
     * in plain text. Only its hash is stored, so this is the one moment the
     * token can be handed out.
     */
    public function issueToken(): string
    {
        $token = Str::random(64);

        $this->forceFill(['token_hash' => self::hashToken($token)]);

        return $token;
    }

    public function hasToken(): bool
    {
        return $this->token_hash !== null;
    }

    public function matchesToken(string $token): bool
    {
        return $this->hasToken() && hash_equals($this->token_hash, self::hashToken($token));
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function questSession(): BelongsTo
    {
        return $this->belongsTo(QuestSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkpointProgress(): HasMany
    {
        return $this->hasMany(CheckpointProgress::class);
    }
}
