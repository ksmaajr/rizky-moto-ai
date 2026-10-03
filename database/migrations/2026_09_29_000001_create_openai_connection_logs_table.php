<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('openai_connection_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('status', 20);
            $table->string('message');
            $table->text('detail')->nullable();

            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('model', 100)->nullable();

            $table->timestamp('tested_at');

            $table->timestamps();

            $table->index(['status', 'tested_at']);
            $table->index('tested_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('openai_connection_logs');
    }
};