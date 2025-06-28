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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('return', 10, 2)->default(0);
            $table->decimal('rate', 5, 2)->default(0);
            $table->char('currency', 3);
            $table->date('due_date');
            $table->enum('status', ['inactive', 'active'])->default('inactive');
            $table->foreignUlid('investor_id')->constrained();
            $table->foreignUlid('invoice_id')->constrained();
            $table->foreignUlid('movement_id')->constrained();
            $table->ulid('previous_investment_id')->nullable();
            $table->ulid('original_investment_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
