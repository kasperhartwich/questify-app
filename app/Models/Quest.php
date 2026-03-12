<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\PlayMode;
use App\Enums\QuestStatus;
use App\Enums\QuestVisibility;
use App\Enums\WrongAnswerBehaviour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Quest extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'category_id',
        'title',
        'description',
        'cover_image_path',
        'difficulty',
        'status',
        'visibility',
        'play_mode',
        'wrong_answer_behaviour',
        'time_limit_per_question',
        'shuffle_questions',
        'shuffle_answers',
        'max_participants',
        'join_code',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => Difficulty::class,
            'status' => QuestStatus::class,
            'visibility' => QuestVisibility::class,
            'play_mode' => PlayMode::class,
            'wrong_answer_behaviour' => WrongAnswerBehaviour::class,
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
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
}
