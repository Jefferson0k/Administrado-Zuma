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
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->decimal('monto_inicial', 12, 2); 
            $table->date('dia_subasta');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->dateTime('tiempo_finalizacion');
            $table->enum('estado', ['pendiente', 'activa', 'finalizada'])->default('pendiente');
            $table->foreignId('ganador_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
