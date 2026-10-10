<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_ai_credentials', function (Blueprint $table) {
            $table->unsignedSmallInteger('cooldown_duration_minutes')->default(300)->after('cooldown_until');
        });
    }

    public function down(): void
    {
        Schema::table('agent_ai_credentials', function (Blueprint $table) {
            $table->dropColumn('cooldown_duration_minutes');
        });
    }
};
