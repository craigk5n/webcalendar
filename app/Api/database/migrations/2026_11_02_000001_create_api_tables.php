<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('webcal_api_users', function (Blueprint $table): void {
      $table->string('username', 100)->primary();
      $table->string('auth_backend');
    });
    Schema::create('webcal_api_tokens', function (Blueprint $table): void {
      $table->id();
      $table->string('tokenable_type');
      $table->string('tokenable_id', 100);
      $table->index(['tokenable_type', 'tokenable_id']);
      $table->string('name');
      $table->string('token', 64)->unique();
      $table->text('abilities')->nullable();
      $table->timestamp('last_used_at')->nullable();
      $table->timestamp('expires_at')->nullable()->index();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('webcal_api_tokens');
    Schema::dropIfExists('webcal_api_users');
  }
};
