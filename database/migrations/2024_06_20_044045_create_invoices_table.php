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
            $table->decimal('amount', 10, 2)->default(0);;
            $table->decimal('financed_amount_by_garantia', 10, 2)->nullable()->default(0);;
            $table->decimal('financed_amount', 10, 2)->default(0);
            $table->bigInteger('paid_amount')->default(0);
            $table->decimal('rate', 5, 2)->default(0);
            $table->date('due_date');
            $table->enum('status', ['inactive', 'active'])->default('inactive');
            $table->foreignUlid('company_id')->constrained();
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
