<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_investor_id')->constrained('property_investors')->onDelete('cascade');
            $table->unsignedInteger('cuota');
            $table->date('vencimiento');

            $table->bigInteger('saldo_inicial')->default(0);
            $table->bigInteger('capital')->default(0);
            $table->bigInteger('intereses')->default(0);
            $table->bigInteger('cuota_neta')->default(0);
            $table->bigInteger('igv')->default(0);
            $table->bigInteger('total_cuota')->default(0);
            $table->bigInteger('saldo_final')->default(0);

            $table->enum('estado', ['pendiente', 'pagado', 'vencido'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('payment_schedules');
    }
};
