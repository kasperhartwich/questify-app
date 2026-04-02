<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Checkpoint extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn (Checkpoint $checkpoint) => $checkpoint->quest->calculateTotalDistance());
        static::deleted(fn (Checkpoint $checkpoint) => $checkpoint->quest->calculateTotalDistance());
    }

    protected $fillable = [
        'quest_id',
        'title',
        'description',
        'latitude',
        'longitude',
        'hint',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function quest(): BelongsTo
    {
        return $this->belongsTo(Quest::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(CheckpointProgress::class);
    }
}
