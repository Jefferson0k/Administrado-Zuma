<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void{
        Schema::create('investment_schedules', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2); // Ej: 200
            $table->unsignedBigInteger('payment_frequency_id');
            $table->unsignedBigInteger('rate_id');
            $table->date('start_date');
            $table->decimal('tax_rate', 5, 2); // Porcentaje ej. 5.00 = 5%
            $table->foreignUlid('investor_id')->constrained('investors')->onDelete('cascade');
            $table->timestamps();
        });
    }
    public function down(): void{
        Schema::dropIfExists('investment_schedules');
    }
};
