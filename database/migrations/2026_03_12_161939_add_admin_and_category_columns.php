<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('locale');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('icon');
            $table->unsignedInteger('sort_order')->default(0)->after('color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['color', 'sort_order']);
        });
    }
};
