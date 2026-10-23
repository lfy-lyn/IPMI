<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('restrict');
            $table->string('nis')->unique();
            $table->string('full_name');
            $table->foreignId('division_id')->constrained('divisions')->onDelete('restrict');
            $table->string('phone')->nullable();
            $table->enum('status', ['active', 'inactive', 'alumni'])->default('active');
            $table->timestamps();
            
            $table->index(['division_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};