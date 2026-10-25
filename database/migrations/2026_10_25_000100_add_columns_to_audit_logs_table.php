<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit log sebelumnya hanya punya id + timestamps (tidak bisa mencatat apa pun).
     * Migrasi baru ini melengkapinya tanpa mengubah migrasi lama.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreignId('actor_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('action', 100)->after('actor_id')->index();
            $table->string('subject_type', 100)->nullable()->after('action');
            $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            $table->text('description')->nullable()->after('subject_id');
            $table->json('metadata')->nullable()->after('description');
            $table->string('ip_address', 45)->nullable()->after('metadata');

            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['actor_id']);
            $table->dropIndex(['subject_type', 'subject_id']);
            $table->dropColumn(['actor_id', 'action', 'subject_type', 'subject_id', 'description', 'metadata', 'ip_address']);
        });
    }
};
