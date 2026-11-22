<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attachments table (rich messages with file/image uploads)
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->nullable()->constrained('messages')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('mime_type');
            $table->integer('file_size')->default(0);
            $table->string('type')->default('file'); // file, image, audio, video, document
            $table->timestamps();

            $table->index('message_id');
            $table->index('type');
        });

        // Flow execution logs table (track chatbot flow runs)
        Schema::create('flow_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->nullable()->constrained('chatbot_configs')->nullOnDelete();
            $table->foreignId('flow_id')->nullable()->constrained('chatbot_flows')->nullOnDelete();
            $table->string('session_id');
            $table->string('conversation_id')->nullable();
            $table->string('current_step')->default('start');
            $table->string('status')->default('running'); // running, completed, error, abandoned
            $table->text('variables')->nullable(); // JSON encoded state variables
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('status');
        });

        // Quick reply for messages
        if (!Schema::hasColumn('messages', 'quick_reply')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('quick_reply')->nullable()->after('type');
            });
        }

        // Rich message type
        if (!Schema::hasColumn('messages', 'rich_type')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->string('rich_type')->nullable()->default('text')->after('type');
            });
        }

        // Add conversation_id to offline_messages for linking
        if (!Schema::hasColumn('offline_messages', 'conversation_id')) {
            Schema::table('offline_messages', function (Blueprint $table) {
                $table->foreignId('conversation_id')->nullable()->after('id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_executions');
        Schema::dropIfExists('attachments');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['quick_reply', 'rich_type', 'file_path']);
        });

        Schema::table('offline_messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
        });
    }
};
