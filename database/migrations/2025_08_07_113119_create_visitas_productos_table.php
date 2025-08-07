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
        Schema::create('visitas_productos', function (Blueprint $table) {
            $table->id();
            $table->string('ip');
            $table->unsignedBigInteger('producto_id');
            $table->timestamps();
            $table->unique(['ip', 'producto_id']);
            $table->foreign('producto_id')->references('id')->on('products')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitas_productos');
    }
};
