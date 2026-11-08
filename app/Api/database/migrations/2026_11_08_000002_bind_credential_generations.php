<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('webcal_api_users', function (Blueprint $table): void {
      $table->string('credential_generation', 64)->nullable();
    });
    Schema::table('webcal_api_tokens', function (Blueprint $table): void {
      $table->string('credential_generation', 64)->nullable();
    });
    foreach (DB::table('webcal_api_users')->get(['username']) as $user) {
      $generation = bin2hex(random_bytes(32));
      DB::table('webcal_api_users')->where('username', $user->username)->update(['credential_generation' => $generation]);
      DB::table('webcal_api_tokens')->where('tokenable_type', 'WebCalendar\\Api\\Models\\ApiUser')
        ->where('tokenable_id', $user->username)->update(['credential_generation' => $generation]);
    }
  }

  public function down(): void
  {
    Schema::table('webcal_api_tokens', fn (Blueprint $table) => $table->dropColumn('credential_generation'));
    Schema::table('webcal_api_users', fn (Blueprint $table) => $table->dropColumn('credential_generation'));
  }
};
