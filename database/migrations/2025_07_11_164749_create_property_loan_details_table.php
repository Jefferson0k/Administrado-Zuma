<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void{
        Schema::create('property_loan_details', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('solicitud_id')->constrained('solicitudes')->onDelete('cascade');
            $table->ulid('config_id'); 
            $table->ulid('investor_id');
            
            $table->string('ocupacion_profesion', 200)->nullable();
            $table->string('empresa_tasadora', 150)->nullable();
            $table->string('motivo_prestamo', 300)->nullable();
            $table->text('descripcion_financiamiento')->nullable();
            $table->text('solicitud_prestamo_para')->nullable();

            $table->unsignedBigInteger('monto_tasacion')->nullable();
            $table->unsignedInteger('porcentaje_prestamo')->nullable();
            $table->unsignedBigInteger('monto_invertir')->nullable();
            $table->unsignedBigInteger('monto_prestamo')->nullable();


            $table->enum('estado_conclusion', [
                'approved','rejected','observed','pendiente'
            ])->default('pendiente');
            $table->enum('approval1_status', ['approved','rejected','observed'])->nullable();
            $table->foreignId('approval1_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval1_comment')->nullable();
            $table->timestamp('approval1_at')->nullable();
            
            // Foreign key investor_id
            $table->foreign('investor_id')
                ->references('id')
                ->on('investors')
                ->onDelete('cascade');

            $table->index(['solicitud_id', 'investor_id']);
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
