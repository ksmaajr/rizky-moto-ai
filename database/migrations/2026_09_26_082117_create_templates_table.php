<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');

            $table->text('description')->nullable();

            /*
             * Example/reference image
             */
            $table->string('example_image_path')->nullable();

            /*
             * AI instruction
             */
            $table->longText('prompt');

            /*
             * Optional negative instruction
             */
            $table->longText('negative_prompt')->nullable();

            /*
             * Output configuration
             */
            $table->string('aspect_ratio')->default('1:1');

            $table->string('output_quality')->default('high');

            /*
             * Template status
             */
            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique([
                'store_id',
                'slug',
            ]);

            $table->index('store_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};