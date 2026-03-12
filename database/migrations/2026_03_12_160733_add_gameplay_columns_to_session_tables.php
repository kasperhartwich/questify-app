<?php

use App\Enums\PlayMode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quest_sessions', function (Blueprint $table) {
            $table->string('play_mode')->default(PlayMode::Solo->value)->after('join_code');
        });

        Schema::table('session_participants', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('user_id');
        });

        Schema::table('checkpoint_progress', function (Blueprint $table) {
            $table->unsignedInteger('wrong_attempts')->default(0)->after('time_taken_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('quest_sessions', function (Blueprint $table) {
            $table->dropColumn('play_mode');
        });

        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });

        Schema::table('checkpoint_progress', function (Blueprint $table) {
            $table->dropColumn('wrong_attempts');
        });
    }
};
