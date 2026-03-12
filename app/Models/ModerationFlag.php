<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModerationFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'flaggable_type',
        'flaggable_id',
        'reporter_id',
        'moderator_id',
        'reason',
        'description',
        'status',
        'resolution_note',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ModerationStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ModerationStatus::Pending);
    }

    public function flaggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }
}
