<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->bigInteger('valor_general')->default(0);
            $table->bigInteger('valor_requerido')->default(0);
            $table->foreignUlid('investor_id')->constrained('investors');
            $table->foreignId('currency_id')->constrained('currencies');
            $table->enum('estado', [
                'en_subasta', 'subastada', 'programada', 'desactivada',
                'activa', 'adquirido', 'pendiente', 'completo', 'espera','rejected','observed'
            ])->default('pendiente');
            $table->unsignedTinyInteger('config_total')->default(0);

            $table->string('fuente_ingreso', 150)->nullable();
            $table->string('profesion_ocupacion', 150)->nullable();
            $table->decimal('ingreso_promedio', 12, 2)->nullable();
            
            $table->enum('approval1_status', ['approved', 'rejected', 'observed'])->nullable();
            $table->foreignId('approval1_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval1_comment')->nullable();
            $table->timestamp('approval1_at')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('solicitudes');
    }
};
