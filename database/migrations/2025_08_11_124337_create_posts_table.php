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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->bigInteger('updated_user_id')->nullable();
            $table->string('titulo')->nullable();
            $table->text('contenido')->nullable();
            $table->string('resumen')->nullable();
            $table->string('imagen')->nullable();
            $table->string('referencias')->nullable();
            $table->foreignId('state_id')->constrained();
            $table->dateTime('fecha_programada')->nullable();
            $table->dateTime('fecha_publicacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
