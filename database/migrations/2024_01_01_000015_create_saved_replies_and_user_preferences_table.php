<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // saved_replies table
        Schema::create('saved_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('category')->nullable()->default('general');
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('user_id');
        });

        // Add theme column to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme')->default('light')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('theme');
        });

        Schema::dropIfExists('saved_replies');
    }
};
