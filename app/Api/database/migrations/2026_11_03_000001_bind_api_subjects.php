<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('webcal_api_users', function (Blueprint $table): void {
      $table->string('subject_id', 64)->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('webcal_api_users', function (Blueprint $table): void {
      $table->dropColumn('subject_id');
    });
  }
};
