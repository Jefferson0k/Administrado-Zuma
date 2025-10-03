<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('properties', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedBigInteger('id_solicitud')->nullable();
            $table->foreignUlid('investor_id')->constrained('investors');
            $table->string('departamento')->nullable();
            $table->string('provincia')->nullable();
            $table->string('distrito')->nullable();
            $table->string('direccion')->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->bigInteger('valor_estimado')->default(0);
            $table->bigInteger('valor_subasta')->default(0);
            $table->bigInteger('valor_requerido')->default(0);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->enum('estado', [
                'en_subasta', 'subastada', 'programada', 'desactivada',
                'activa', 'adquirido', 'pendiente', 'completo', 'espera'
            ])->default('pendiente');
            $table->unsignedTinyInteger('config_total')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('properties');
    }
};
