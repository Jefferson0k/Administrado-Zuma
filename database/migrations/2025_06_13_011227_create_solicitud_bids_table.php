<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('solicitud_bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')
                ->constrained('solicitudes')
                ->onDelete('cascade');
            $table->foreignUlid('investor_id')
                ->constrained('investors')
                ->onDelete('cascade');
            $table->foreignUlid('ganador_id')
                ->nullable()
                ->constrained('investors')
                ->nullOnDelete();
            $table->enum('estado', ['pendiente', 'ganador', 'rechazado'])
                ->default('pendiente');
            $table->foreignId('created_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('deleted_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // 🔹 Índices
            $table->index(['solicitud_id', 'investor_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('solicitud_bids');
    }
};
