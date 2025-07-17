<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('investor_property_status', function (Blueprint $table) {
            $table->id();

            $table->ulid('investor_id');
            $table->ulid('property_id');
            $table->decimal('monto', 15, 2)->default(0.00);
            $table->enum('estado', ['pendiente', 'aceptado', 'rechazado'])->default('pendiente');

            $table->timestamps();

            $table->foreign('investor_id')->references('id')->on('investors')->onDelete('cascade');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('investor_property_status');
    }
};
