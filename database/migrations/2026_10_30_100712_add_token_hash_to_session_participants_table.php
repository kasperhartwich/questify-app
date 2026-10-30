<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SHA-256 of the participant token handed out once at join. Null for a
     * participant who joined from a build that predates tokens.
     */
    public function up(): void
    {
        Schema::table('session_participants', function (Blueprint $table) {
            $table->string('token_hash', 64)->nullable()->after('display_name');
        });
    }

    public function down(): void
    {
        Schema::table('session_participants', function (Blueprint $table) {
            $table->dropColumn('token_hash');
        });
    }
};
