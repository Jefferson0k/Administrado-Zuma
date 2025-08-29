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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('bank_id')->constrained('banks');
            $table->string('type');
            $table->char('currency', 3);
            $table->string('cc');
            $table->string('cci');
            $table->string('alias')->nullable();
            $table->enum('status', ['valid', 'invalid', 'pre_approved'])->default('invalid');
            $table->foreignUlid('investor_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
