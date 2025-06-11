<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary(); // ID único ordenable
            $table->uuid('invoice_code')->unique(); // Código externo de la factura

            // Montos financieros
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('financed_amount_by_garantia', 10, 2)->nullable()->default(0);
            $table->decimal('financed_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('rate', 5, 2)->default(0);

            // Fecha de vencimiento y estado
            $table->date('due_date');
            $table->enum('status', ['inactive', 'active'])->default('inactive');

            // Relaciones desactivadas (solo campos nulos)
            $table->ulid('company_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
