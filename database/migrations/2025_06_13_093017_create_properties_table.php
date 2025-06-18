<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('distrito');
            $table->text('descripcion')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('validado')->default(false);
            $table->date('fecha_inversion')->nullable();

            $table->enum('estado', [
                'no_subastada',   // Aún no se ha hecho nada
                'programada',     // Ya tiene fecha y hora de subasta
                'en_subasta',     // Subasta activa
                'subastada',      // Subasta terminada con ganador
                'desierta'        // Subasta terminada sin participantes
            ])->default('no_subastada');

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('properties');
    }
};
