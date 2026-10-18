<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nvidia_api_keys')) {
            return;
        }

        Schema::create('nvidia_api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->text('api_key');
            $table->string('status', 32)->default('active')->index();
            $table->unsignedBigInteger('request_count')->default(0);
            $table->unsignedBigInteger('success_count')->default(0);
            $table->unsignedBigInteger('failure_count')->default(0);
            $table->string('last_error_type', 80)->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedSmallInteger('last_http_status')->nullable();
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamp('cooldown_until')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nvidia_api_keys');
    }
};
