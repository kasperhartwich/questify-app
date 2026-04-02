<?php

namespace Database\Seeders;

use App\Models\ActivityType;
use Illuminate\Database\Seeder;

class ActivityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // Auth
            ['key' => 'user_registered', 'name' => 'Registered', 'icon' => 'user_plus', 'show_in_app' => false],
            ['key' => 'user_logged_in', 'name' => 'Logged in', 'icon' => 'login', 'show_in_app' => false],
            ['key' => 'user_logged_out', 'name' => 'Logged out', 'icon' => 'logout', 'show_in_app' => false],
            ['key' => 'password_reset_requested', 'name' => 'Password reset requested', 'icon' => 'key', 'show_in_app' => false],
            ['key' => 'password_reset', 'name' => 'Password reset', 'icon' => 'key', 'show_in_app' => false],
            ['key' => 'social_unlinked', 'name' => 'Social provider unlinked', 'icon' => 'unlink', 'show_in_app' => false],

            // Profile
            ['key' => 'profile_updated', 'name' => 'Profile updated', 'icon' => 'user', 'show_in_app' => false],
            ['key' => 'account_deleted', 'name' => 'Account deleted', 'icon' => 'trash', 'show_in_app' => false],

            // Quests — shown in app
            ['key' => 'quest_created', 'name' => 'Created new quest', 'icon' => 'pencil', 'show_in_app' => true],
            ['key' => 'quest_updated', 'name' => 'Updated quest', 'icon' => 'pencil', 'show_in_app' => false],
            ['key' => 'quest_published', 'name' => 'Published new quest', 'icon' => 'map_pin', 'show_in_app' => true],
            ['key' => 'quest_archived', 'name' => 'Archived quest', 'icon' => 'archive', 'show_in_app' => false],
            ['key' => 'quest_completed', 'name' => 'Completed quest', 'icon' => 'checkmark', 'show_in_app' => true],
            ['key' => 'quest_shared', 'name' => 'Shared a quest', 'icon' => 'share', 'show_in_app' => true],
            ['key' => 'quest_rated', 'name' => 'Rated a quest', 'icon' => 'star', 'show_in_app' => true],
            ['key' => 'quest_favourited', 'name' => 'Favourited a quest', 'icon' => 'heart', 'show_in_app' => true],
            ['key' => 'quest_flagged', 'name' => 'Flagged a quest', 'icon' => 'flag', 'show_in_app' => false],

            // Sessions
            ['key' => 'session_created', 'name' => 'Created session', 'icon' => 'play', 'show_in_app' => false],
            ['key' => 'session_joined', 'name' => 'Joined session', 'icon' => 'login', 'show_in_app' => false],
            ['key' => 'session_started', 'name' => 'Started session', 'icon' => 'play', 'show_in_app' => false],
            ['key' => 'session_ended', 'name' => 'Ended session', 'icon' => 'stop', 'show_in_app' => false],

            // Gameplay
            ['key' => 'checkpoint_arrived', 'name' => 'Arrived at checkpoint', 'icon' => 'location', 'show_in_app' => false],
            ['key' => 'answer_submitted', 'name' => 'Submitted answer', 'icon' => 'check', 'show_in_app' => false],

            // Phone verification
            ['key' => 'phone_submitted', 'name' => 'Phone number submitted', 'icon' => 'phone', 'show_in_app' => false],
            ['key' => 'phone_verified', 'name' => 'Phone number verified', 'icon' => 'phone', 'show_in_app' => false],
        ];

        foreach ($types as $type) {
            ActivityType::query()->updateOrCreate(
                ['key' => $type['key']],
                $type,
            );
        }
    }
}
