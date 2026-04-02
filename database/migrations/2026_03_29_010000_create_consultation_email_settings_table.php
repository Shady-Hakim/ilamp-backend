<?php

use App\Models\ConsultationEmailSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_email_settings', function (Blueprint $table): void {
            $table->id();
            $table->longText('client_email_body')->nullable();
            $table->longText('pending_email_body')->nullable();
            $table->longText('confirmed_email_body')->nullable();
            $table->longText('cancelled_email_body')->nullable();
            $table->longText('completed_email_body')->nullable();
            $table->longText('no_show_email_body')->nullable();
            $table->timestamps();
        });

        DB::table('consultation_email_settings')->insert(array_merge(
            ConsultationEmailSetting::defaultBodies(),
            [
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ));
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_email_settings');
    }
};
