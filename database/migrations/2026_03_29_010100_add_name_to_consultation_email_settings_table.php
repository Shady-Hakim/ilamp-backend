<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_email_settings', function (Blueprint $table): void {
            $table->string('name')->default('Consultation Emails')->after('id');
        });

        DB::table('consultation_email_settings')
            ->whereNull('name')
            ->orWhere('name', '')
            ->update([
                'name' => 'Consultation Emails',
            ]);
    }

    public function down(): void
    {
        Schema::table('consultation_email_settings', function (Blueprint $table): void {
            $table->dropColumn('name');
        });
    }
};
