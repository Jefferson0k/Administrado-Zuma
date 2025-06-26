<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('deadlines_id')->nullable()->constrained('deadlines');
            $table->decimal('monto_invertido', 15, 2);
            $table->date('fecha_inversion')->nullable();
            $table->enum('estado', ['pendiente', 'confirmado', 'cancelado', 'activa', 'finalizada'])->default('pendiente');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('investments');
    }
};
