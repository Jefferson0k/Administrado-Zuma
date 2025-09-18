<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void{
        Schema::create('property_loan_details', function (Blueprint $table) {
            $table->id();
            $table->ulid('property_id');
            $table->ulid('config_id'); // Agregado para la configuración
            $table->ulid('investor_id');
            
            // Campos con límites específicos según el frontend
            $table->string('ocupacion_profesion', 200)->nullable(); // 200 caracteres
            $table->string('empresa_tasadora', 150)->nullable(); // 150 caracteres
            $table->string('motivo_prestamo', 300)->nullable(); // 300 caracteres
            $table->text('descripcion_financiamiento')->nullable(); // 500 caracteres (text para mayor flexibilidad)
            $table->text('solicitud_prestamo_para')->nullable(); // 500 caracteres (text para mayor flexibilidad)

            // === NUEVOS CAMPOS ===
            $table->unsignedBigInteger('monto_tasacion')->nullable();     // Monto de la tasación
            $table->unsignedInteger('porcentaje_prestamo')->nullable();   // % para préstamo (entero 0-100)
            $table->unsignedBigInteger('monto_invertir')->nullable();     // Monto a invertir
            $table->unsignedBigInteger('monto_prestamo')->nullable();     // Monto del préstamo

            // Foreign keys
            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->onDelete('cascade');

            $table->foreign('investor_id')
                ->references('id')
                ->on('investors')
                ->onDelete('cascade');
                
            $table->index(['property_id', 'investor_id']);
            $table->index('config_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_loan_details');
    }
};
