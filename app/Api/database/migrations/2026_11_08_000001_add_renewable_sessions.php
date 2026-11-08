<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('webcal_api_tokens', function (Blueprint $table): void {
      $table->unsignedBigInteger('refresh_id')->nullable()->index();
      $table->string('rotation_hash', 64)->nullable();
      $table->timestamp('rotated_at')->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('webcal_api_tokens', function (Blueprint $table): void {
      $table->dropIndex(['refresh_id']);
      $table->dropColumn(['refresh_id', 'rotation_hash', 'rotated_at']);
    });
  }
};
