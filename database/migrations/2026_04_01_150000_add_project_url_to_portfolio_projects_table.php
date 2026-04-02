<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table): void {
            $table->string('project_url')->nullable()->after('year');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_projects', function (Blueprint $table): void {
            $table->dropColumn('project_url');
        });
    }
};
