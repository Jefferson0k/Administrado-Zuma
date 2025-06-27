<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            
            $table->string('departamento')->nullable();
            $table->string('provincia')->nullable();
            $table->string('distrito')->nullable();
            $table->string('direccion')->nullable();

            $table->string('nombre');
            $table->text('descripcion')->nullable();

            $table->decimal('valor_estimado', 15, 2)->nullable();
            $table->decimal('valor_subasta', 15, 2)->nullable();

            $table->foreignId('currency_id')->constrained('currencies');
            $table->foreignId('deadlines_id')->nullable()->constrained('deadlines');

            $table->decimal('tea', 6, 4)->nullable();
            $table->decimal('tem', 6, 4)->nullable();

            $table->enum('estado', [
                'en_subasta', 'subastada', 'programada', 'desactivada', 'activa'
            ])->default('activa');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('properties');
    }
};
