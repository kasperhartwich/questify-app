<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New quests default to a 30 m arrival radius, matching the admin and the
     * gameplay fallback. Existing rows keep the radius they have.
     */
    public function up(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->unsignedInteger('checkpoint_arrival_radius_meters')->nullable()->default(30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('quests', function (Blueprint $table) {
            $table->unsignedInteger('checkpoint_arrival_radius_meters')->nullable()->default(50)->change();
        });
    }
};
