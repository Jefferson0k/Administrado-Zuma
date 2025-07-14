<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void {
        Schema::create('fixed_term_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_term_investment_id')->constrained('fixed_term_investments')->onDelete('cascade');
            $table->integer('month');
            $table->date('payment_date');
            $table->integer('days');
            $table->decimal('base_amount', 15, 2);
            $table->decimal('interest_amount', 15, 2);
            $table->decimal('second_category_tax', 15, 2)->default(0);
            $table->decimal('interest_to_deposit', 15, 2);
            $table->decimal('capital_return', 15, 2);
            $table->decimal('capital_balance', 15, 2);
            $table->decimal('total_to_deposit', 15, 2);
            $table->enum('status', ['pendiente', 'pagado', 'vencido'])->default('pendiente');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('fixed_term_schedules');
    }
};
