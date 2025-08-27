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
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('invoice_code');
            $table->string('codigo')->unique();
            $table->char('currency', 3);
            $table->bigInteger('amount')->default(0);
            $table->bigInteger('financed_amount_by_garantia')->default(0);
            $table->bigInteger('financed_amount')->default(0);
            $table->bigInteger('paid_amount')->default(0);
            $table->decimal('rate', 5, 2)->default(0);
            $table->date('due_date');
            $table->date('estimated_pay_date')->nullable();
            $table->enum('status', ['inactive', 'active', 'expired', 'judicialized', 'reprogramed', 'paid', 'canceled'])->default('inactive');
            $table->foreignUlid('company_id')->constrained();

            $table->string('loan_number')->nullable();
            $table->string('invoice_number')->nullable();
            $table->char('RUC_client', 20)->nullable();

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
