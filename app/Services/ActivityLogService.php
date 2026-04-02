<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /** @param array<string, mixed> $metadata */
    public function log(User $user, ActivityType $type, Model $subject, array $metadata = []): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $user->id,
            'type' => $type,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->id,
            'metadata' => $metadata,
        ]);
    }
}
