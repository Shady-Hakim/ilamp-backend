<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_availability_overrides', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('mode')->default('add');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('slot_duration_minutes')->nullable();
            $table->unsignedSmallInteger('buffer_minutes')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_availability_overrides');
    }
};
