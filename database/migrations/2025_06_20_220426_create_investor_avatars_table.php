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
        Schema::create('investor_avatars', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('investor_id')->constrained()->onDelete('cascade');
            $table->smallInteger('avatar_type')->comment('Opcion seleccionada por el usuario');
            $table->string('clothing_color')->comment('Color de la ropa'); # ejemplo:  '#000000'
            $table->string('background_color')->comment('Color de fondo'); # ejemplo: '#FFFFFF'
            $table->smallInteger('hat')->nullable()->comment('Sombrero'); # ejemplo: '2'
            $table->json('hat_position')->nullable()->comment('Posicion del sombrero'); # ejemplo: '{"x": 10, "y": 10}'
            $table->smallInteger('medal')->nullable()->comment('Medalla'); # ejemplo: '1'
            $table->json('medal_position')->nullable()->comment('Posicion de la medalla'); # ejemplo: '{"x": 10, "y": 10}'
            $table->smallInteger('trophy')->nullable()->comment('Trofeo'); # ejemplo: '3'
            $table->smallInteger('other')->nullable()->comment('Otro'); # ejemplo: '4'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_avatars');
    }
};
