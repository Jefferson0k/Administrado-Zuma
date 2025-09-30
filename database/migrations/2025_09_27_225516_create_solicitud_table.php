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
        Schema::create('solicitud', function (Blueprint $table) {
            $table->id('id_solicitud'); // PK
            $table->string('numero_solicitud')->unique();
            $table->char('id_investors', 26); // FK hacia investors.id
            $table->dateTime('fecha_solicitud');
            $table->timestamps();

            // Relación con investors
            $table->foreign('id_investors')->references('id')->on('investors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitud');
    }
};
