<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_category_project', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('portfolio_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('portfolio_project_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['portfolio_category_id', 'portfolio_project_id'], 'pcp_cat_proj_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_category_project');
    }
};
