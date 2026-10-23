<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->string('nis')->index()->after('id');
            $table->string('full_name')->after('nis');
            $table->string('email')->after('full_name');
            $table->string('phone')->nullable()->after('email');
            $table->text('address')->nullable()->after('phone');
            $table->foreignId('division_id')->after('address')->constrained('divisions')->onDelete('restrict');
            $table->text('reason')->after('division_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending')->after('reason');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->foreignId('reviewed_by')->nullable()->after('rejection_reason')->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            
            $table->index(['status', 'created_at']);
        });

        Schema::table('activation_tokens', function (Blueprint $table) {
            $table->foreignId('member_id')->after('id')->constrained('members')->onDelete('cascade');
            $table->string('token')->unique()->after('member_id');
            $table->timestamp('expires_at')->after('token');
            $table->timestamp('used_at')->nullable()->after('expires_at');
            
            $table->index(['member_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('activation_tokens', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropIndex(['member_id', 'expires_at']);
            $table->dropColumn(['member_id', 'token', 'expires_at', 'used_at']);
        });

        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['nis', 'full_name', 'email', 'phone', 'address', 'division_id', 'reason', 'status', 'rejection_reason', 'reviewed_by', 'reviewed_at']);
        });
    }
};
