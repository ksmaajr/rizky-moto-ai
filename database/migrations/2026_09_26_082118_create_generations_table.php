<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->id();

            /*
             * User yang menjalankan generation
             */
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            /*
             * Store yang digunakan
             */
            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            /*
             * Template yang digunakan
             */
            $table->foreignId('template_id')
                ->constrained('templates')
                ->restrictOnDelete();

            /*
             * Input images
             */
            $table->string('product_image_1_path')->nullable();
            $table->string('product_image_2_path')->nullable();
            $table->string('reference_image_path')->nullable();

            /*
             * Snapshot konfigurasi.
             *
             * Kita simpan prompt/config yang digunakan
             * saat generation dibuat.
             *
             * Jadi kalau template berubah di masa depan,
             * history lama tetap memiliki konfigurasi aslinya.
             */
            $table->longText('prompt')->nullable();
            $table->longText('negative_prompt')->nullable();

            $table->string('aspect_ratio')->nullable();
            $table->string('output_quality')->nullable();

            /*
             * API information
             */
            $table->string('model')->nullable();

            $table->string('status')->default('pending');

            $table->text('error_message')->nullable();

            /*
             * Optional metadata
             */
            $table->json('metadata')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
            $table->index('store_id');
            $table->index('template_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};