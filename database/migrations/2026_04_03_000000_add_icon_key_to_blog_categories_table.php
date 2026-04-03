<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blog_categories', function (Blueprint $table): void {
            $table->string('icon_key')->nullable()->after('description');
        });

        $defaults = [
            'ai' => 'BrainCircuit',
            'web-development' => 'Code',
            'digital-marketing' => 'Megaphone',
            'seo' => 'Search',
            'saas' => 'Cloud',
            'technology' => 'Cpu',
            'business-growth' => 'BarChart3',
        ];

        foreach ($defaults as $slug => $iconKey) {
            DB::table('blog_categories')
                ->where('slug', $slug)
                ->whereNull('icon_key')
                ->update(['icon_key' => $iconKey]);
        }
    }

    public function down(): void
    {
        Schema::table('blog_categories', function (Blueprint $table): void {
            $table->dropColumn('icon_key');
        });
    }
};
