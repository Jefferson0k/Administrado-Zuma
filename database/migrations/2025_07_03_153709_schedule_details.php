<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_schedule_id')->constrained('investment_schedules')->onDelete('cascade');

            $table->unsignedTinyInteger('month'); // mes
            $table->date('schedule');         // fecha de pago
            $table->date('payment_date');         // fecha de pago payment date
            $table->unsignedInteger('days');      // días
            $table->decimal('base_amount', 12, 2);
            $table->decimal('base_interest', 12, 2);
            $table->decimal('second_category_tax', 12, 2);
            $table->decimal('net_interest', 12, 2);
            $table->decimal('capital_return', 12, 2);
            $table->decimal('capital_balance', 12, 2);
            $table->decimal('total_payment', 12, 2); // total a depositar

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_details');
    }
};
