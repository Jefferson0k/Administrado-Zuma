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
        Schema::create('history_aprobadores_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('deposit_id')->nullable()->constrained('deposits')->nullOnDelete();
            $table->enum('approval1_status', ['pending', 'approved', 'rejected', 'observed',])->default('pending');
            $table->foreignId('approval1_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval1_comment')->nullable();
            $table->timestamp('approval1_at')->nullable();
            $table->enum('approval2_status', ['pending', 'approved', 'rejected', 'observed'])->nullable();
            $table->foreignId('approval2_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval2_comment')->nullable();
            $table->timestamp('approval2_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_aprobadores_deposits');
    }
};
