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
        Schema::table('incidental_activities', function (Blueprint $table) {
            $table->string('name')->after('id');
            $table->text('description')->nullable()->after('name');
            $table->date('start_date')->after('description');
            $table->date('end_date')->nullable()->after('start_date');
            $table->foreignId('division_id')->nullable()->after('end_date')->constrained('divisions')->onDelete('set null');
            $table->enum('attendance_mode', ['per_event', 'per_day'])->default('per_event')->after('division_id');
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming')->after('attendance_mode');
            $table->foreignId('created_by')->after('status')->constrained('users')->onDelete('cascade');
        });

        Schema::table('activity_days', function (Blueprint $table) {
            $table->foreignId('incidental_activity_id')->after('id')->constrained('incidental_activities')->onDelete('cascade');
            $table->date('date')->after('incidental_activity_id');
            $table->string('description')->nullable()->after('date');
        });

        Schema::table('activity_attendances', function (Blueprint $table) {
            $table->foreignId('member_id')->after('id')->constrained('members')->onDelete('cascade');
            $table->foreignId('incidental_activity_id')->after('member_id')->constrained('incidental_activities')->onDelete('cascade');
            $table->foreignId('activity_day_id')->nullable()->after('incidental_activity_id')->constrained('activity_days')->onDelete('cascade');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir')->after('activity_day_id');
            $table->text('notes')->nullable()->after('status');
            
            $table->unique(['member_id', 'incidental_activity_id', 'activity_day_id'], 'activity_attendance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_attendances', function (Blueprint $table) {
            $table->dropUnique('activity_attendance_unique');
            $table->dropForeign(['member_id']);
            $table->dropForeign(['incidental_activity_id']);
            $table->dropForeign(['activity_day_id']);
            $table->dropColumn(['member_id', 'incidental_activity_id', 'activity_day_id', 'status', 'notes']);
        });

        Schema::table('activity_days', function (Blueprint $table) {
            $table->dropForeign(['incidental_activity_id']);
            $table->dropColumn(['incidental_activity_id', 'date', 'description']);
        });

        Schema::table('incidental_activities', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['name', 'description', 'start_date', 'end_date', 'division_id', 'attendance_mode', 'status', 'created_by']);
        });
    }
};
