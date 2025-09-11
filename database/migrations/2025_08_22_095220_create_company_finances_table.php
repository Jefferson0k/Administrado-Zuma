<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_finances', function (Blueprint $table) {
            $table->id();
            $table->char('company_id', 26);

            // Montos generales
            $table->unsignedInteger('facturas_financiadas')->nullable();
            $table->decimal('monto_total_financiado', 15, 2)->nullable();
            $table->unsignedInteger('pagadas')->nullable();
            $table->unsignedInteger('pendientes')->nullable();
            $table->unsignedInteger('plazo_promedio_pago')->nullable();

            // Datos en PEN
            $table->decimal('sales_volume_pen', 15, 2)->nullable();
            $table->integer('facturas_financiadas_pen')->nullable();
            $table->decimal('monto_total_financiado_pen', 15, 2)->nullable();
            $table->integer('pagadas_pen')->nullable();
            $table->integer('pendientes_pen')->nullable();
            $table->integer('plazo_promedio_pago_pen')->nullable();

            // Datos en USD
            $table->decimal('sales_volume_usd', 15, 2)->nullable();
            $table->integer('facturas_financiadas_usd')->nullable();
            $table->decimal('monto_total_financiado_usd', 15, 2)->nullable();
            $table->integer('pagadas_usd')->nullable();
            $table->integer('pendientes_usd')->nullable();
            $table->integer('plazo_promedio_pago_usd')->nullable();

            // Auditoría
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            // Relaciones e índices
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_finances');
    }
};
