<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckpointProgress extends Model
{
    use HasFactory;

    protected $table = 'checkpoint_progress';

    protected $fillable = [
        'session_participant_id',
        'checkpoint_id',
        'question_id',
        'answer_id',
        'open_ended_answer',
        'is_correct',
        'points_earned',
        'time_taken_seconds',
        'wrong_attempts',
    ];

    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
        ];
    }

    public function sessionParticipant(): BelongsTo
    {
        return $this->belongsTo(SessionParticipant::class);
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(Checkpoint::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
