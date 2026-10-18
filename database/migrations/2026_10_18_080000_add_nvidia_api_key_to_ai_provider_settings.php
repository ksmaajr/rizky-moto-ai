<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_provider_settings', function (Blueprint $table): void {
            $table->text('nvidia_api_key')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_provider_settings', function (Blueprint $table): void {
            $table->dropColumn('nvidia_api_key');
        });
    }
};
