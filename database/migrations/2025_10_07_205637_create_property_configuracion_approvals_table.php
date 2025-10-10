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
        Schema::create('property_configuracion_approvals', function (Blueprint $table) {
            $table->id();

            // 🔗 Relación con la configuración
            // ⛔️ IMPORTANTE: cambia este nombre si tu tabla tiene otro nombre
            $table->foreignId('configuracion_id')
                ->constrained('property_configuracions') // <-- cámbialo aquí si tu tabla es diferente
                ->onDelete('cascade');

            // 🔗 Usuario que aprueba
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // 🔘 Estado de la aprobación
            $table->enum('status', ['approved', 'rejected', 'observed'])
                ->default('observed');

            // 💬 Comentario de la aprobación
            $table->text('comment')->nullable();

            // 🕒 Fecha y hora de aprobación
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_configuracion_approvals');
    }
};
