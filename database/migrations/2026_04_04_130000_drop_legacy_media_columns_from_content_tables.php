<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('blog_posts', 'image_url')) {
            Schema::table('blog_posts', function (Blueprint $table): void {
                $table->dropColumn('image_url');
            });
        }

        Schema::table('portfolio_projects', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('portfolio_projects', 'image_url')) {
                $columns[] = 'image_url';
            }

            if (Schema::hasColumn('portfolio_projects', 'client_logo_url')) {
                $columns[] = 'client_logo_url';
            }

            if (Schema::hasColumn('portfolio_projects', 'gallery')) {
                $columns[] = 'gallery';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('blog_posts', 'image_url')) {
                $table->string('image_url')->nullable()->after('author_name');
            }
        });

        Schema::table('portfolio_projects', function (Blueprint $table): void {
            if (! Schema::hasColumn('portfolio_projects', 'image_url')) {
                $table->string('image_url')->nullable()->after('tech_stack');
            }

            if (! Schema::hasColumn('portfolio_projects', 'client_logo_url')) {
                $table->string('client_logo_url')->nullable()->after('client_brief');
            }

            if (! Schema::hasColumn('portfolio_projects', 'gallery')) {
                $table->json('gallery')->nullable()->after('results');
            }
        });
    }
};
