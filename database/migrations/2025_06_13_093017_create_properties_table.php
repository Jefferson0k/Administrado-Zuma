<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void{
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('distrito');
            $table->text('descripcion')->nullable();
            $table->string('foto')->nullable();
            $table->boolean('validado')->default(false);
            $table->date('fecha_inversion')->nullable();
            $table->enum('estado', [
                'no_subastada',
                'en_subasta',
                'subastada',
                'desierta'
            ])->default('no_subastada');
            $table->timestamps();
        });
    }
    public function down(): void{
        Schema::dropIfExists('properties');
    }
};
