<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_crawls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->nullable()->constrained('chatbot_configs')->nullOnDelete();
            $table->string('url')->unique();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('domain')->nullable();
            $table->string('crawl_type')->default('single');
            $table->integer('status')->default(0);
            $table->text('error_message')->nullable();
            $table->integer('word_count')->default(0);
            $table->integer('chunk_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamp('next_crawl_at')->nullable();
            $table->timestamps();

            $table->index('chatbot_id');
            $table->index('domain');
            $table->index('status');
            $table->index('next_crawl_at');
        });

        Schema::create('crawl_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->nullable()->constrained('chatbot_configs')->nullOnDelete();
            $table->string('name');
            $table->string('base_url');
            $table->json('allowed_paths')->nullable();
            $table->json('disallowed_paths')->nullable();
            $table->integer('max_pages')->default(50);
            $table->integer('max_depth')->default(3);
            $table->boolean('respect_robots')->default(true);
            $table->boolean('crawl_sitemap')->default(false);
            $table->string('schedule')->default('daily');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_crawled_at')->nullable();
            $table->integer('total_crawled')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamps();

            $table->index('chatbot_id');
            $table->index('is_active');
        });

        Schema::create('web_crawl_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('web_crawl_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->integer('chunk_index')->default(0);
            $table->integer('word_count')->default(0);
            $table->string('source_url');
            $table->json('embedding')->nullable();
            $table->timestamps();

            $table->index('web_crawl_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_crawl_chunks');
        Schema::dropIfExists('crawl_sources');
        Schema::dropIfExists('web_crawls');
    }
};
