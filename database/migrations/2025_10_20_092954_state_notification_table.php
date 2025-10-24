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
        Schema::create('state_notification', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('investor_id')->constrained('investors');
            $table->integer('status');
            $table->enum('type', [
                'datos_personales', 
                'cuenta_bancaria', 
                'primer_deposito', 
                'espera_confirmacion_deposito',
                'confirmacion_deposito',
                'rechazo_cuenta',
                'rechazo_deposito',
                'pago_inversionn',
                'factura_reprogramada',
                'solicitud_retiro',
                'rechazo_retiro',
                'aceptacion_retiro',
                'pago_parcial_sin_reprogramacion',
                'pago_parcial_con_reprogramacion'
                ])->default('datos_personales');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_notification');
    }
};
