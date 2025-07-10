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
            $table->foreignId('deadlines_id')->nullable()->constrained('deadlines');

            $table->decimal('tea', 6, 4)->nullable();
            $table->decimal('tem', 6, 4)->nullable();

            $table->enum('tipo_cronograma', ['frances', 'americano', '-'])->default('-');

            $table->enum('riesgo', ['A+', 'A', 'B', 'C', 'D','-'])->default('-');

            $table->enum('estado', [
                'en_subasta', 'subastada', 'programada', 'desactivada', 'activa', 'adquirido'
            ])->default('activa');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('properties');
    }
};
