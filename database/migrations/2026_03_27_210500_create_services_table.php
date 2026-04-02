<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->string('icon_key')->nullable();
            $table->json('features')->nullable();
            $table->string('headline')->nullable();
            $table->string('subheadline')->nullable();
            $table->longText('description')->nullable();
            $table->json('benefits')->nullable();
            $table->json('process_steps')->nullable();
            $table->json('faq_items')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
