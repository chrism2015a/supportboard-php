<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agent ratings table
        Schema::create('agent_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_conversation_id')->nullable()->constrained('chatbot_conversations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('conversation_id')->nullable();
            $table->integer('rating'); // 1-5 stars
            $table->text('feedback')->nullable();
            $table->string('response')->nullable();
            $table->timestamps();

            $table->index('conversation_id');
        });

        // Smart notes table
        Schema::create('smart_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('summary');
            $table->text('action_items')->nullable();
            $table->text('sentiment')->nullable();
            $table->string('sentiment_score')->nullable(); // positive/neutral/negative
            $table->timestamps();

            $table->index('conversation_id');
        });

        // Offline messages table
        Schema::create('offline_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message');
            $table->string('subject')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('new'); // new, assigned, in_progress, resolved
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('department_id');
        });

        // Routing rules table
        Schema::create('routing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('conditions')->nullable(); // JSON string of conditions
            $table->string('condition_type')->default('and'); // and/or
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('priority')->default(5); // 1 = highest priority
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        // Add file_path to messages table (for rich messages)
        if (!Schema::hasColumn('messages', 'file_path')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('file_path')->nullable()->after('text');
            });
        }

        // Add quick_buttons to conversations table (for rich messages)
        if (!Schema::hasColumn('conversations', 'quick_buttons')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->text('quick_buttons')->nullable()->after('subject');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_rules');
        Schema::dropIfExists('offline_messages');
        Schema::dropIfExists('smart_notes');
        Schema::dropIfExists('agent_ratings');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('quick_buttons');
        });
    }
};
