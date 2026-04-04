<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('recaptcha_site_key')->nullable()->after('response_time_text');
            $table->string('recaptcha_secret_key')->nullable()->after('recaptcha_site_key');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['recaptcha_site_key', 'recaptcha_secret_key']);
        });
    }
};
