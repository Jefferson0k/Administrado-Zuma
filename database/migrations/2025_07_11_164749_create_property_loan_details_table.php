<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('property_loan_details', function (Blueprint $table) {
            $table->id();
            $table->ulid('property_id');
            $table->unsignedBigInteger('customer_id');

            $table->string('ocupacion_profesion')->nullable();
            $table->string('motivo_prestamo')->nullable();
            $table->text('descripcion_financiamiento')->nullable();
            $table->string('solicitud_prestamo_para')->nullable();
            $table->string('garantia')->nullable();
            $table->string('perfil_riesgo')->nullable();
            
            $table->timestamps();

            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->onDelete('cascade');

            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('property_loan_details');
    }
};
