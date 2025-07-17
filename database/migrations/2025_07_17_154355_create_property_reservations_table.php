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
        Schema::create('property_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('investor_id')->constrained('investors')->onDelete('cascade');
            $table->foreignUlid('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('config_id')->nullable()->constrained('property_configuracions')->onDelete('cascade');
            $table->decimal('amount', 12, 2)->nullable();
            $table->enum('status', ['pendiente', 'reservado', 'pagado', 'cancelado'])->default('pendiente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_reservations');
    }
};
