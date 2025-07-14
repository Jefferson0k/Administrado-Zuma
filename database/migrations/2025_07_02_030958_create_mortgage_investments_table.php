<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mortgage_investments', function (Blueprint $table) {
            $table->id();

            $table->foreignUlid('investor_id')->constrained('investors');

            $table->foreignUlid('property_id')->constrained('properties');

            $table->foreignId('deadline_id')->constrained('deadlines');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('product_account_id')->constrained('product_accounts');

            $table->decimal('monthly_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3);

            $table->enum('status', ['pendiente', 'en_progreso', 'finalizado'])->default('pendiente');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('mortgage_investments');
    }
};
