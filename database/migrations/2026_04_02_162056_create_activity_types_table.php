<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->boolean('show_in_app')->default(false);
            $table->timestamps();
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreignId('activity_type_id')->nullable()->after('type')->constrained('activity_types')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('activity_type_id');
        });

        Schema::dropIfExists('activity_types');
    }
};
