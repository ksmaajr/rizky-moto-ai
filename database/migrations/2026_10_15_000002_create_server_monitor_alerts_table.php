<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_monitor_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key', 64)->index();
            $table->string('severity', 16)->default('warning');
            $table->string('title', 160);
            $table->text('message');
            $table->decimal('observed_value', 12, 2)->nullable();
            $table->decimal('threshold_value', 12, 2)->nullable();
            $table->timestamp('started_at')->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();
            $table->index(['metric_key', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_monitor_alerts');
    }
};
