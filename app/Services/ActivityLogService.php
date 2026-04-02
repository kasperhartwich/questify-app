<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ActivityLogService
{
    /** @param array<string, mixed> $metadata */
    public function log(User $user, string $typeKey, ?Model $subject = null, array $metadata = []): ActivityLog
    {
        $activityTypeId = $this->resolveTypeId($typeKey);

        return ActivityLog::create([
            'user_id' => $user->id,
            'activity_type_id' => $activityTypeId,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->id,
            'metadata' => $metadata ?: null,
        ]);
    }

    private function resolveTypeId(string $key): int
    {
        /** @var array<string, int> $map */
        $map = Cache::remember('activity_type_map', 3600, function (): array {
            return ActivityType::query()->pluck('id', 'key')->all();
        });

        return $map[$key] ?? throw new \InvalidArgumentException("Unknown activity type: {$key}");
    }
}
