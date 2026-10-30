<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Sessions whose participants have all finished the quest were never completed. Safe to rerun.
     */
    public function up(): void
    {
        DB::table('quest_sessions')
            ->whereIn('status', ['waiting', 'active'])
            ->whereExists(fn ($query) => $query->select(DB::raw(1))
                ->from('session_participants')
                ->whereColumn('session_participants.quest_session_id', 'quest_sessions.id'))
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                ->from('session_participants')
                ->whereColumn('session_participants.quest_session_id', 'quest_sessions.id')
                ->whereNull('session_participants.finished_at'))
            ->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
    }

    public function down(): void {}
};
