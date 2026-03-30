<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quests')->where('play_mode', 'competitive')->update(['play_mode' => 'competitive_individual']);
        DB::table('quests')->where('play_mode', 'cooperative')->update(['play_mode' => 'competitive_teams']);
        DB::table('quests')->where('visibility', 'unlisted')->update(['visibility' => 'school']);

        DB::table('quest_sessions')->where('play_mode', 'competitive')->update(['play_mode' => 'competitive_individual']);
        DB::table('quest_sessions')->where('play_mode', 'cooperative')->update(['play_mode' => 'competitive_teams']);
        DB::table('quest_sessions')->where('status', 'in_progress')->update(['status' => 'active']);
        DB::table('quest_sessions')->where('status', 'abandoned')->update(['status' => 'completed']);

        DB::table('questions')->where('type', 'open_ended')->update(['type' => 'open_text']);
    }

    public function down(): void
    {
        DB::table('quests')->where('play_mode', 'competitive_individual')->update(['play_mode' => 'competitive']);
        DB::table('quests')->where('play_mode', 'competitive_teams')->update(['play_mode' => 'cooperative']);
        DB::table('quests')->where('visibility', 'school')->update(['visibility' => 'unlisted']);

        DB::table('quest_sessions')->where('play_mode', 'competitive_individual')->update(['play_mode' => 'competitive']);
        DB::table('quest_sessions')->where('play_mode', 'competitive_teams')->update(['play_mode' => 'cooperative']);
        DB::table('quest_sessions')->where('status', 'active')->update(['status' => 'in_progress']);

        DB::table('questions')->where('type', 'open_text')->update(['type' => 'open_ended']);
    }
};
