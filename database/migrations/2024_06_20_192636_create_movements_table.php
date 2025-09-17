<?php
use App\Enums\MovementStatus;
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
                'payment',
                'deposit',
                'withdraw',
                'investment',
                'tax',
                'exchange_up',
                'exchange_down',
                'fixed_rate_disbursement',
                'fixed_rate_interest_payment',
                'fixed_rate_capital_return',
                'mortgage_disbursement',
                'mortgage_installment_payment',
                'mortgage_early_payment',
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
            $table->enum('origin', ['cliente', 'inversionista', 'zuma'])->default('zuma');
            $table->foreignUlid('investor_id')->nullable()->constrained();
            $table->foreignUlid('related_movement_id')->nullable()->constrained('movements');
            
            $table->timestamp('aprobacion_1')->nullable();
            $table->string('aprobado_por_1')->nullable();
            $table->foreignId('approval1_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('aprobacion_2')->nullable();
            $table->string('aprobado_por_2')->nullable();
            $table->foreignId('approval2_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            
            $table->index(['aprobacion_1', 'aprobacion_2']);
            $table->index(['status', 'confirm_status']);
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