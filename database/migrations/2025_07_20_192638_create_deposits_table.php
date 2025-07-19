<?php

use App\Enums\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('nro_operation', 255);
            $table->decimal('amount', 10, 2);
            $table->enum('currency', [
                Currency::PEN->value,
                Currency::USD->value
            ]);
            $table->string('resource_path', 2048)->nullable();
            $table->longText('description')->nullable();

            $table->foreignUlid('investor_id')->constrained();
            $table->foreignUlid('movement_id')->constrained();
            $table->foreignUlid('bank_account_id')->nullable()->constrained(); // ✅ Corrección aquí

            $table->string('payment_source')->nullable();
            $table->string('type')->nullable();

            $table->foreignId('fixed_term_investment_id')
                  ->nullable()
                  ->constrained('fixed_term_investments')
                  ->onDelete('cascade');
            $table->foreignId('property_reservations_id')
                  ->nullable()
                  ->constrained('property_reservations')
                  ->onDelete('cascade');
            $table->foreignId('payment_schedules_id')
                  ->nullable()
                  ->constrained('payment_schedules')
                  ->onDelete('cascade');

            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
