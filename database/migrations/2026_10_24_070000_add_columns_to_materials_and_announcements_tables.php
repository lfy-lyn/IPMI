<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->foreignId('division_id')->after('id')->constrained('divisions')->onDelete('cascade');
            $table->string('title')->after('division_id');
            $table->text('description')->nullable()->after('title');
            $table->string('file_path')->nullable()->after('description');
            $table->string('external_url')->nullable()->after('file_path');
            $table->enum('status', ['active', 'archived'])->default('active')->after('external_url');
            $table->foreignId('created_by')->after('status')->constrained('users')->onDelete('cascade');
        });

        Schema::table('announcements', function (Blueprint $table) {
            $table->string('title')->after('id');
            $table->text('content')->after('title');
            $table->enum('target_type', ['all', 'division', 'pengurus'])->default('all')->after('content');
            $table->foreignId('division_id')->nullable()->after('target_type')->constrained('divisions')->onDelete('set null');
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft')->after('division_id');
            $table->timestamp('published_at')->nullable()->after('status');
            $table->foreignId('created_by')->after('published_at')->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['title', 'content', 'target_type', 'division_id', 'status', 'published_at', 'created_by']);
        });

        Schema::table('materials', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['division_id', 'title', 'description', 'file_path', 'external_url', 'status', 'created_by']);
        });
    }
};
