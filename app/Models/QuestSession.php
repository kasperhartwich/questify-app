<?php

namespace App\Models;

use App\Enums\PlayMode;
use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'quest_id',
        'host_id',
        'status',
        'join_code',
        'play_mode',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'play_mode' => PlayMode::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [SessionStatus::Waiting, SessionStatus::Active]);
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class);
    }
}
