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
            $table->string('empresa_tasadora', 150)->nullable(); // 150 caracteres - NUEVO CAMPO
            $table->string('motivo_prestamo', 300)->nullable(); // 300 caracteres
            $table->text('descripcion_financiamiento')->nullable(); // 500 caracteres (text para mayor flexibilidad)
            $table->string('solicitud_prestamo_para', 250)->nullable(); // 250 caracteres
            $table->string('garantia', 250)->nullable(); // 250 caracteres
            $table->string('perfil_riesgo', 400)->nullable(); // 400 caracteres
            // Foreign keys
            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->onDelete('cascade');

            $table->foreign('investor_id')
                ->references('id')
                ->on('investors')
                ->onDelete('cascade');
                
            // Índices para mejorar performance en consultas
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
