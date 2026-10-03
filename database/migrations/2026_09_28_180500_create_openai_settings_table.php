<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('openai_settings', function (Blueprint $table) {
            $table->id();

            // Stored through Laravel's encrypted cast in OpenAiSetting.
            $table->text('api_key')->nullable();

            $table->string('model', 100)->default('OpenAI Image Generation');
            $table->string('default_aspect_ratio', 20)->default('1:1');
            $table->string('default_quality', 30)->default('standard');

            $table->boolean('is_active')->default(true);

            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 30)->nullable();
            $table->text('last_test_message')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openai_settings');
    }
};
