<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->string('name')->unique()->after('id');
            $table->text('description')->nullable()->after('name');
            $table->enum('status', ['active', 'inactive'])->default('active')->after('description');
        });

        Schema::table('member_positions', function (Blueprint $table) {
            $table->foreignId('member_id')->after('id')->constrained('members')->onDelete('cascade');
            $table->foreignId('position_id')->after('member_id')->constrained('positions')->onDelete('cascade');
            
            $table->unique(['member_id', 'position_id']);
        });
    }

    public function down(): void
    {
        Schema::table('member_positions', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropForeign(['position_id']);
            $table->dropUnique(['member_id', 'position_id']);
            $table->dropColumn(['member_id', 'position_id']);
        });

        Schema::table('positions', function (Blueprint $table) {
            $table->dropColumn(['name', 'description', 'status']);
        });
    }
};
