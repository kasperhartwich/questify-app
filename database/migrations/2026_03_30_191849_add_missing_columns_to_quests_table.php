<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->unsignedInteger('estimated_duration_minutes')->nullable()->after('wrong_answer_behaviour');
            $table->string('access_code', 20)->nullable()->after('visibility');
            $table->unsignedInteger('checkpoint_arrival_radius_meters')->nullable()->default(50)->after('estimated_duration_minutes');
            $table->unsignedInteger('wrong_answer_penalty_points')->nullable()->default(0)->after('wrong_answer_behaviour');
            $table->unsignedInteger('wrong_answer_lockout_seconds')->nullable()->default(0)->after('wrong_answer_penalty_points');
            $table->unsignedInteger('scoring_points_per_correct')->nullable()->default(100)->after('wrong_answer_lockout_seconds');
            $table->boolean('scoring_speed_bonus_enabled')->default(true)->after('scoring_points_per_correct');
            $table->boolean('scoring_wrong_attempt_penalty_enabled')->default(true)->after('scoring_speed_bonus_enabled');
            $table->boolean('scoring_quest_completion_time_bonus_enabled')->default(true)->after('scoring_wrong_attempt_penalty_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->dropColumn([
                'estimated_duration_minutes',
                'access_code',
                'checkpoint_arrival_radius_meters',
                'wrong_answer_penalty_points',
                'wrong_answer_lockout_seconds',
                'scoring_points_per_correct',
                'scoring_speed_bonus_enabled',
                'scoring_wrong_attempt_penalty_enabled',
                'scoring_quest_completion_time_bonus_enabled',
            ]);
        });
    }
};
