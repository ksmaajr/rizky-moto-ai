<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generated_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('generation_id')
                ->constrained('generations')
                ->cascadeOnDelete();

            /*
             * Generated image
             */
            $table->string('image_path');

            /*
             * Optional public URL / external storage URL
             */
            $table->text('image_url')->nullable();

            /*
             * AI response information
             */
            $table->string('model')->nullable();

            $table->string('format')->default('png');

            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            /*
             * Optional metadata from API
             */
            $table->json('metadata')->nullable();

            /*
             * Mark primary/favorite result
             */
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_favorite')->default(false);

            $table->timestamps();

            $table->index('generation_id');
            $table->index('is_primary');
            $table->index('is_favorite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_images');
    }
};