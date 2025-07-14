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
        Schema::create('mortgage_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('property_id')->constrained();
            $table->foreignId('mortgage_investment_id')->nullable()->constrained();
            $table->integer('installment_number');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->boolean('paid')->default(false);
            $table->timestamps();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mortgage_payment_schedules');
    }
};
