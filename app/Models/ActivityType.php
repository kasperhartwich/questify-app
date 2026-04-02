<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityType extends Model
{
    protected $fillable = [
        'key',
        'name',
        'icon',
        'show_in_app',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'show_in_app' => 'boolean',
        ];
    }

    /** @return HasMany<ActivityLog, $this> */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
