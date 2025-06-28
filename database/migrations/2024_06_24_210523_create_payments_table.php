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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('invoice_id')->constrained('invoices');
            $table->enum('pay_type', ['total', 'partial'])->default('total');
            $table->bigInteger('amount_to_be_paid')->default(0);
            $table->datetime('pay_date');
            $table->date('reprogramation_date');
            $table->decimal('reprogramation_rate', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
