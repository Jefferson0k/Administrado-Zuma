<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('properties', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('departamento')->nullable();
            $table->string('provincia')->nullable();
            $table->string('distrito')->nullable();
            $table->string('direccion')->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('valor_estimado', 15, 2)->nullable();
            $table->decimal('valor_subasta', 15, 2)->nullable();
            $table->decimal('valor_requerido', 15, 2);
            $table->foreignId('currency_id')->constrained('currencies');

            $table->enum('estado', [
                'en_subasta',     // En proceso de subasta
                'subastada',      // Subasta finalizada
                'programada',     // En espera de iniciar subasta
                'desactivada',    // No visible ni activa
                'activa',         // Activa para mostrar
                'adquirido',      // Ya fue comprado
                'pendiente',      // Registro en espera
                'completo',        // Ya tiene configuración de inversionista y cliente
                'espera'
            ])->default('pendiente');

            // 👉 Aquí agregas la nueva columna
            $table->unsignedTinyInteger('config_total')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('properties');
    }
};
