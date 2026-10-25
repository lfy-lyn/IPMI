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
        Schema::table('academic_years', function (Blueprint $table) {
            $table->string('name')->unique()->after('id');
            $table->date('start_date')->after('name');
            $table->date('end_date')->after('start_date');
            $table->enum('status', ['upcoming', 'active', 'archived'])->default('upcoming')->after('end_date');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->after('id')->constrained('academic_years')->onDelete('cascade');
            $table->string('grade_level')->after('academic_year_id');
            $table->string('major')->after('grade_level');
            $table->integer('class_number')->after('major');
            $table->string('name')->after('class_number');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('name');
            
            $table->unique(['academic_year_id', 'grade_level', 'major', 'class_number'], 'classes_unique');
        });

        Schema::table('member_class_histories', function (Blueprint $table) {
            $table->foreignId('member_id')->after('id')->constrained('members')->onDelete('cascade');
            $table->foreignId('class_id')->after('member_id')->constrained('classes')->onDelete('cascade');
            
            $table->unique(['member_id', 'class_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_class_histories', function (Blueprint $table) {
            $table->dropUnique(['member_id', 'class_id']);
            $table->dropForeign(['member_id']);
            $table->dropForeign(['class_id']);
            $table->dropColumn(['member_id', 'class_id']);
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropUnique('classes_unique');
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn(['academic_year_id', 'grade_level', 'major', 'class_number', 'name', 'status']);
        });

        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropColumn(['name', 'start_date', 'end_date', 'status']);
        });
    }
};
