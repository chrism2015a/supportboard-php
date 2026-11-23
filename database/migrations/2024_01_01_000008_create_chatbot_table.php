<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->text('default_message')->nullable();
            $table->text('welcome_message')->nullable();
            $table->text('fallback_message')->nullable();
            $table->boolean('human_takeover_on_fallback')->default(true);
            $table->boolean('auto_translate')->default(false);
            $table->boolean('speech_recognition')->default(false);
            $table->boolean('image_analysis')->default(false);
            $table->boolean('web_search')->default(false);
            $table->boolean('conversation_history')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('chatbot_qnas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained('chatbot_configs')->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->string('category')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
        });

        Schema::create('chatbot_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained('chatbot_configs')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('steps');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('chatbot_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained('chatbot_configs')->cascadeOnDelete();
            $table->date('date');
            $table->integer('total_conversations')->default(0);
            $table->integer('resolved_by_bot')->default(0);
            $table->integer('transferred_to_human')->default(0);
            $table->decimal('avg_response_time', 8, 2)->default(0);
            $table->decimal('satisfaction_score', 3, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_analytics');
        Schema::dropIfExists('chatbot_flows');
        Schema::dropIfExists('chatbot_qnas');
        Schema::dropIfExists('chatbot_configs');
    }
};
