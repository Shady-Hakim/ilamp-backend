<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_category_post', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('blog_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blog_category_id', 'blog_post_id'], 'bcp_cat_post_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_category_post');
    }
};
