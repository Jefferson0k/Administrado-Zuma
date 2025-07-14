<?php

use App\Enums\MovementStatus;
use App\Enums\MovementType;
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
        Schema::create('movements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->decimal('amount', 10, 2);
            $table->enum('type', [
                MovementType::PAYMENT->value,
                MovementType::DEPOSIT->value,
                MovementType::WITHDRAW->value,
                MovementType::INVESTMENT->value,
                MovementType::TAX->value,
                MovementType::EXCHANGE_UP->value,
                MovementType::EXCHANGE_DOWN->value,
            ]);
            $table->char('currency', 3);
            $table->enum('status', [
                MovementStatus::VALID->value,
                MovementStatus::INVALID->value,
                MovementStatus::PENDING->value,
                MovementStatus::REJECTED->value,
                MovementStatus::CONFIRMED->value
            ])->default(MovementStatus::PENDING->value);
            $table->enum('confirm_status', [
                MovementStatus::VALID->value,
                MovementStatus::INVALID->value,
                MovementStatus::PENDING->value,
                MovementStatus::REJECTED->value,
                MovementStatus::CONFIRMED->value
            ])->default(MovementStatus::PENDING->value);
            $table->string('description')->nullable();

            $table->enum('origin', ['cliente', 'inversionista', 'zuma'])->default('zuma'); // ✅ nuevo
            $table->foreignUlid('investor_id')->nullable()->constrained();
            $table->foreignUlid('related_movement_id')->nullable()->constrained('movements'); // ✅ nuevo

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
