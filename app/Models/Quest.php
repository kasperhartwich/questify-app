<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\QuestStatus;
use App\Enums\QuestVisibility;
use App\Enums\WrongAnswerBehaviour;
use App\Models\Concerns\HasImageUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Quest extends Model
{
    use HasFactory, HasImageUrls;

    protected $fillable = [
        'creator_id',
        'category_id',
        'title',
        'description',
        'cover_image_path',
        'difficulty',
        'status',
        'visibility',
        'access_code',
        'wrong_answer_behaviour',
        'wrong_answer_penalty_points',
        'wrong_answer_lockout_seconds',
        'estimated_duration_minutes',
        'total_distance_km',
        'checkpoint_arrival_radius_meters',
        'scoring_points_per_correct',
        'scoring_speed_bonus_enabled',
        'scoring_wrong_attempt_penalty_enabled',
        'scoring_quest_completion_time_bonus_enabled',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => Difficulty::class,
            'status' => QuestStatus::class,
            'visibility' => QuestVisibility::class,
            'wrong_answer_behaviour' => WrongAnswerBehaviour::class,
            'scoring_speed_bonus_enabled' => 'boolean',
            'scoring_wrong_attempt_penalty_enabled' => 'boolean',
            'scoring_quest_completion_time_bonus_enabled' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', QuestStatus::Published);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('visibility', QuestVisibility::Public);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function checkpoints(): HasMany
    {
        return $this->hasMany(Checkpoint::class)->orderBy('sort_order');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuestSession::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(QuestRating::class);
    }

    public function moderationFlags(): MorphMany
    {
        return $this->morphMany(ModerationFlag::class, 'flaggable');
    }

    public function favouritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quest_favourites')->withTimestamps();
    }

    public function calculateTotalDistance(): void
    {
        $checkpoints = $this->checkpoints()->orderBy('sort_order')->get();

        $totalDistance = 0.0;

        for ($i = 1; $i < $checkpoints->count(); $i++) {
            $totalDistance += self::haversineDistance(
                (float) $checkpoints[$i - 1]->latitude,
                (float) $checkpoints[$i - 1]->longitude,
                (float) $checkpoints[$i]->latitude,
                (float) $checkpoints[$i]->longitude,
            );
        }

        $this->updateQuietly(['total_distance_km' => round($totalDistance, 2)]);
    }

    public static function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
