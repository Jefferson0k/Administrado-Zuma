<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->uuid('invoice_code');
            $table->string('codigo')->unique();
            $table->char('currency', 3);
            $table->bigInteger('amount')->default(0);
            $table->bigInteger('financed_amount_by_garantia')->default(0);
            $table->bigInteger('financed_amount')->default(0);
            $table->bigInteger('paid_amount')->default(0);
            $table->decimal('rate', 5, 2)->default(0);
            $table->date('due_date');
            $table->date('estimated_pay_date')->nullable();
            $table->enum('status', [
                'inactive',
                'active',
                'rejected',
                'observed',
                'daStandby'
            ])->default('inactive');

            $table->enum('statusPago', [
                'paid',
                'reprogramed',
            ])->default('inactive');

            $table->enum('type', [
                'normal',
                'annulled',
                'reprogramed',
            ])->nullable();


            $table->foreignUlid('company_id')->constrained();

            $table->string('loan_number')->nullable();
            $table->string('invoice_number')->nullable();
            $table->char('RUC_client', 20)->nullable();

            // --- Aprobaciones ---
            $table->enum('approval1_status', ['approved', 'rejected', 'observed'])->nullable();
            $table->foreignId('approval1_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval1_comment')->nullable();
            $table->timestamp('approval1_at')->nullable();
            
            $table->enum('approval2_status', ['approved', 'rejected', 'observed'])->nullable();
            $table->foreignId('approval2_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval2_comment')->nullable();
            $table->timestamp('approval2_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
