<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kegiatan rutin (D-014): Program memiliki banyak Sesi; setiap sesi punya presensi sendiri.
     * Tabel sebelumnya hanya berisi id + timestamps.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('schedule_note', 255)->nullable(); // mis. "Setiap Jumat 06.30"
            $table->foreignId('division_id')->nullable()->constrained('divisions')->restrictOnDelete(); // null = seluruh IPMI
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table('program_sessions', function (Blueprint $table) {
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->date('session_date');
            $table->time('start_time')->nullable();
            $table->string('agenda', 500)->nullable();
            $table->index(['program_id', 'session_date']);
        });

        Schema::table('program_session_attendances', function (Blueprint $table) {
            $table->foreignId('program_session_id')->constrained('program_sessions')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->restrictOnDelete();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa']);
            $table->string('notes', 500)->nullable();
            $table->unique(['program_session_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::table('program_session_attendances', function (Blueprint $table) {
            $table->dropForeign(['program_session_id']);
            $table->dropForeign(['member_id']);
            $table->dropUnique(['program_session_id', 'member_id']);
            $table->dropColumn(['program_session_id', 'member_id', 'status', 'notes']);
        });
        Schema::table('program_sessions', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropIndex(['program_id', 'session_date']);
            $table->dropColumn(['program_id', 'session_date', 'start_time', 'agenda']);
        });
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['name', 'description', 'schedule_note', 'division_id', 'status', 'created_by']);
        });
    }
};
