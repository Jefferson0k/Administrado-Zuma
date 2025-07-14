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
        Schema::create('exchanges', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->bigInteger('exchange_rate_sell')->default(0);
            $table->bigInteger('exchange_rate_buy')->default(0);
            $table->char('currency', 3);
            $table->enum('status', ['inactive', 'active'])->default('inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchanges');
    }
};
