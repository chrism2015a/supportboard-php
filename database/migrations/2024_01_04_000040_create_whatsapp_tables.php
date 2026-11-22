<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add external_id to conversations table for WhatsApp integration
        if (!Schema::hasColumn('conversations', 'external_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->string('external_id')->nullable()->after('status');
            });
        }

        // Add whatsapp_id to users table
        if (!Schema::hasColumn('users', 'whatsapp_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('whatsapp_id')->nullable()->after('phone');
            });
        }

        // Add whatsapp_message table to log sent/received messages
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_phone')->nullable();
            $table->string('to_phone')->nullable();
            $table->string('wa_message_id')->unique()->nullable();
            $table->string('direction')->default('inbound'); // inbound, outbound
            $table->text('content');
            $table->string('status')->default('sent'); // sent, delivered, read, failed
            $table->timestamps();

            $table->index('wa_message_id');
            $table->index('direction');
            $table->index('conversation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('whatsapp_id');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('external_id');
        });
    }
};
