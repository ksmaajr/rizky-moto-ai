<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_ai_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->text('access_token');
            $table->boolean('is_active')->default(true);
            $table->string('status', 30)->default('active');
            $table->unsignedInteger('request_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->timestamp('cooldown_until')->nullable();
            $table->string('last_error_type', 80)->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedSmallInteger('last_exit_code')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active', 'status']);
            $table->index('cooldown_until');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_ai_credentials');
    }
};
