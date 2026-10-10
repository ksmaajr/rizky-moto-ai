<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_ai_credentials', function (Blueprint $table) {
            $table->unsignedSmallInteger('cooldown_duration_minutes')->default(1440)->change();
        });

        // Upgrade the previous 5-hour default while preserving custom per-account values.
        DB::table('agent_ai_credentials')
            ->where('cooldown_duration_minutes', 300)
            ->update(['cooldown_duration_minutes' => 1440]);
    }

    public function down(): void
    {
        DB::table('agent_ai_credentials')
            ->where('cooldown_duration_minutes', 1440)
            ->update(['cooldown_duration_minutes' => 300]);

        Schema::table('agent_ai_credentials', function (Blueprint $table) {
            $table->unsignedSmallInteger('cooldown_duration_minutes')->default(300)->change();
        });
    }
};
