<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('property_configuracions', function (Blueprint $table) {
            $table->id();
            $table->ulid('property_id');
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreignId('deadlines_id')->nullable()->constrained('deadlines')->onDelete('set null');
            $table->bigInteger('tea')->nullable();
            $table->bigInteger('tem')->nullable();
            $table->enum('tipo_cronograma', ['frances', 'americano','-'])->default('-');
            $table->enum('riesgo', ['A+', 'A', 'B', 'C', 'D', '-'])->default('-');
            $table->integer('estado');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('property_configuracions');
    }
};
