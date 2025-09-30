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
        Schema::create('detalle_inversionista_hipoteca', function (Blueprint $table) {
            $table->id('id_detalle_inversionista_hipoteca');
            $table->string('fuente_ingreso', 150)->nullable();
            $table->string('profesion_ocupacion', 150)->nullable();
            $table->decimal('ingreso_promedio', 12, 2)->nullable();

            // Clave foránea con investors            
            $table->foreignUlid('investor_id')->constrained('investors')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_inversionista_hipoteca');
    }
};
