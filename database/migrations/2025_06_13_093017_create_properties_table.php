<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('properties', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('investor_id')->constrained('investors');
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
                'en_subasta', 'subastada', 'programada', 'desactivada',
                'activa', 'adquirido', 'pendiente', 'completo', 'espera'
            ])->default('pendiente');
            $table->unsignedTinyInteger('config_total')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('properties');
    }
};
