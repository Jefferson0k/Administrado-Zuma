<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('property_loan_detail_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_detail_id')->constrained('property_loan_details')->cascadeOnDelete();
            $table->enum('status', ['approved', 'rejected', 'observed', 'pendiente']);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('approved_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('property_loan_detail_approvals');
    }
};
