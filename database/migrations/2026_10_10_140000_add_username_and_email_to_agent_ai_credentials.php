<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_ai_credentials', function (Blueprint $table) {
            $table->string('username', 190)->nullable()->after('name');
            $table->string('email', 254)->nullable()->after('username')->index();
        });
    }

    public function down(): void
    {
        Schema::table('agent_ai_credentials', function (Blueprint $table) {
            $table->dropIndex(['email']);
            $table->dropColumn(['username', 'email']);
        });
    }
};
