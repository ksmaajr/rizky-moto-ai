<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('api_keys',function(Blueprint $table){
   $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
   $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
   $table->string('name',120); $table->string('prefix',24)->unique(); $table->char('key_hash',64)->unique();
   $table->json('scopes'); $table->unsignedSmallInteger('rate_limit')->default(60);
   $table->unsignedBigInteger('usage_count')->default(0); $table->timestamp('last_used_at')->nullable();
   $table->timestamp('expires_at')->nullable(); $table->timestamp('revoked_at')->nullable(); $table->timestamps();
   $table->index(['user_id','revoked_at']); $table->index(['store_id','revoked_at']);
  });
 }
 public function down(): void { Schema::dropIfExists('api_keys'); }
};