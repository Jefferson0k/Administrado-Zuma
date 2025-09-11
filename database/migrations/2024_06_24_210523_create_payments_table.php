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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('invoice_id')
            ->nullable()
            ->constrained('invoices');
            $table->enum('pay_type', ['total', 'partial', 'reembloso'])->default('total');
            $table->bigInteger('amount_to_be_paid')->default(0);
            $table->datetime('pay_date');
            $table->date('reprogramation_date');
            $table->decimal('reprogramation_rate', 5, 2)->default(0);

            $table->enum('approval1_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approval1_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval1_comment')->nullable();
            $table->timestamp('approval1_at')->nullable();

            $table->enum('approval2_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approval2_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval2_comment')->nullable();
            $table->timestamp('approval2_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
