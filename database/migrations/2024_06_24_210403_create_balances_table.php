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
        Schema::create('balances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->bigInteger('amount')->default(0);
            $table->bigInteger('invested_amount')->default(0);
            $table->bigInteger('expected_amount')->default(0);
            $table->char('currency', 3);
            $table->foreignUlid('investor_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balances');
    }
};
