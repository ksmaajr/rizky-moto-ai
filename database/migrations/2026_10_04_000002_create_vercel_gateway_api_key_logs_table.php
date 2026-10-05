<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vercel_gateway_api_key_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('vercel_gateway_api_key_id')
                ->nullable()
                ->constrained('vercel_gateway_api_keys')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('status', 20);
            $table->string('message');
            $table->text('detail')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error_type', 60)->nullable();
            $table->timestamp('tested_at');
            $table->timestamps();

            // Explicit short index names to stay below MySQL's 64-character limit.
            $table->index(
                ['vercel_gateway_api_key_id', 'tested_at'],
                'vgakl_key_tested_idx'
            );

            $table->index(
                ['status', 'tested_at'],
                'vgakl_status_tested_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vercel_gateway_api_key_logs');
    }
};
