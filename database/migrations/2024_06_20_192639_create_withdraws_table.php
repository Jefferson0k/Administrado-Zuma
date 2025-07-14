<?php

use App\Enums\Currency;
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
        Schema::create('withdraws', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nro_operation', 255)->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('currency', [
                Currency::PEN->value,
                Currency::USD->value
            ]);
            $table->date('deposit_pay_date')->nullable();
            $table->string('resource_path', 2048)->nullable();
            $table->longText('description')->nullable();
            $table->string('purpouse')->nullable();

            $table->foreignUlid('movement_id')->constrained();
            $table->foreignUlid('investor_id')->constrained();
            $table->foreignUlid('bank_account_id')->constrained();

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraws');
    }
};
