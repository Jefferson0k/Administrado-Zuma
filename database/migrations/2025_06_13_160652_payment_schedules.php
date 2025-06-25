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

            $table->foreignId('investment_id')->constrained('investments')->onDelete('cascade');
            $table->integer('nro_cuota');
            $table->date('fecha_vencimiento');

            $table->decimal('saldo_inicial', 15, 2);
            $table->decimal('capital', 15, 2);
            $table->decimal('interes', 15, 2);
            $table->decimal('cuota_neta', 15, 2);
            $table->decimal('igv', 15, 2);
            $table->decimal('cuota_total', 15, 2);
            $table->decimal('saldo_final', 15, 2);

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(table: 'payment_schedules');
    }
};
