<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'quest_session_id',
        'user_id',
        'display_name',
        'current_checkpoint_index',
        'score',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'finished_at' => 'datetime',
        ];
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
