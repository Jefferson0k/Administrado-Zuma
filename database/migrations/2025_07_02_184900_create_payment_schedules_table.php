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
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('property_investor_id')->constrained('property_investor')->onDelete('cascade');

            $table->unsignedInteger('cuota');
            $table->date('vencimiento');
            $table->decimal('saldo_inicial', 12, 2);
            $table->decimal('capital', 12, 2);
            $table->decimal('intereses', 12, 2);
            $table->decimal('cuota_neta', 12, 2);
            $table->decimal('igv', 12, 2);
            $table->decimal('total_cuota', 12, 2);
            $table->decimal('saldo_final', 12, 2);

            $table->enum('estado', ['pendiente', 'pagado', 'vencido'])->default('pendiente');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_schedules');
    }
};
