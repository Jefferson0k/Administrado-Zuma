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
        Schema::create('mortgage_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mortgage_investment_id')->constrained('mortgage_investments')->onDelete('cascade');
            $table->date('paid_at');
            $table->decimal('amount', 12, 2);
            $table->string('method')->nullable(); // transferencia, billetera, etc.
            $table->string('referencia')->nullable(); // nro operación u observación
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mortgage_payments');
    }
};
