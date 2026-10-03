<?php

use App\Models\ActivityLog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 40)->index();
            $table->string('action', 100)->index();
            $table->string('status', 20)->index();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->timestamps();

            $table->index(['category', 'status', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });

        // Preserve existing OpenAI connection history when the old table exists.
        if (Schema::hasTable('openai_connection_logs')) {
            $rows = DB::table('openai_connection_logs')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                ActivityLog::query()->create([
                    'user_id' => $row->user_id,
                    'category' => 'api',
                    'action' => 'test_openai_connection',
                    'status' => $row->status,
                    'title' => $row->status === 'success'
                        ? 'OpenAI API berhasil terhubung.'
                        : 'Test koneksi OpenAI gagal.',
                    'description' => $row->detail ?: $row->message,
                    'metadata' => [
                        'legacy_log_id' => $row->id,
                        'model' => $row->model,
                        'source' => 'openai_connection_logs',
                    ],
                    'duration_ms' => $row->duration_ms,
                    'http_status' => $row->http_status,
                    'created_at' => $row->tested_at ?? $row->created_at,
                    'updated_at' => $row->created_at ?? $row->tested_at,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
