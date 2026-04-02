<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill existing activity_logs with the correct activity_type_id
        $types = DB::table('activity_types')->pluck('id', 'key');
        foreach ($types as $key => $id) {
            DB::table('activity_logs')->where('type', $key)->update(['activity_type_id' => $id]);
        }

        // Drop orphaned rows without a valid type
        DB::table('activity_logs')->whereNull('activity_type_id')->delete();

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->foreignId('activity_type_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('type')->after('user_id');
        });
    }
};
