<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $keep = DB::table('checkpoint_progress')
            ->selectRaw('MAX(id) as id')
            ->groupBy('session_participant_id', 'question_id')
            ->pluck('id');

        DB::table('checkpoint_progress')->whereNotIn('id', $keep)->delete();

        Schema::table('checkpoint_progress', function (Blueprint $table) {
            $table->unique(['session_participant_id', 'question_id'], 'checkpoint_progress_participant_question_unique');
        });
    }

    public function down(): void
    {
        Schema::table('checkpoint_progress', function (Blueprint $table) {
            $table->dropUnique('checkpoint_progress_participant_question_unique');
        });
    }
};
