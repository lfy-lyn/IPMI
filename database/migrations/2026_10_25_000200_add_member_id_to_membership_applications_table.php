<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Mengikat pendaftaran ke anggota yang dihasilkannya (bukan lewat kecocokan email). */
    public function up(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('reviewed_at')->constrained('members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('membership_applications', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropColumn('member_id');
        });
    }
};
