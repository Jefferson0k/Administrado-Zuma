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
        Schema::create('company_finances', function (Blueprint $table) {
            $table->id();
            $table->string('company_id'); // Cambiar a string para que coincida con el ULID
            $table->integer('facturas_financiadas')->default(0);
            $table->decimal('monto_total_financiado', 15, 2)->default(0.00);
            $table->integer('pagadas')->default(0);
            $table->integer('pendientes')->default(0);
            $table->integer('plazo_promedio_pago')->default(0);
            $table->enum('moneda', ['soles', 'dolares'])->default('soles');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Índices y relaciones - Especificar el tipo de campo correctamente
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'moneda']);
            $table->unique(['company_id', 'moneda']); // Una empresa puede tener solo un registro por moneda
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_finances');
    }
};