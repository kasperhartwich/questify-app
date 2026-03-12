<?php

namespace App\Models;

use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'checkpoint_id',
        'type',
        'body',
        'image_path',
        'hint',
        'points',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
        ];
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('sort_order');
    }

    public function correctAnswer(): HasMany
    {
        return $this->hasMany(Answer::class)->where('is_correct', true);
    }
}
