<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
     public function up(): void {
        Schema::create('fixed_term_investments', function (Blueprint $table) {
            $table->id();
            $table->ulid('investor_id');
            $table->foreign('investor_id')->references('id')->on('investors')->onDelete('cascade');
            $table->foreignId('fixed_term_rate_id')->constrained('fixed_term_rates')->onDelete('cascade');
            $table->foreignId('term_plan_id')->constrained('term_plans')->onDelete('cascade');
            $table->foreignId('payment_frequency_id')->constrained('payment_frequencies')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pendiente', 'activo', 'finalizado', 'cancelado'])->default('pendiente');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('fixed_term_investments');
    }
};
