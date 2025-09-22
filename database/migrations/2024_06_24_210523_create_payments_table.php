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

            // Relación con invoices
            $table->foreignUlid('invoice_id')
                ->nullable()
                ->constrained('invoices');

            // Datos del pago
            $table->enum('pay_type', ['total', 'partial', 'reembloso'])->default('total');
            $table->bigInteger('amount_to_be_paid')->default(0);
            $table->datetime('pay_date');
            $table->date('reprogramation_date')->nullable();
            $table->decimal('reprogramation_rate', 5, 2)->default(0);

            // Evidencias
            $table->json('evidencia')->nullable();                // lista de nombres de archivos
            $table->json('evidencia_data')->nullable();           // metadata en JSON
            $table->integer('evidencia_count')->nullable();       // cantidad de archivos
            $table->string('evidencia_path')->nullable();         // ruta en disco
            $table->string('evidencia_original_name')->nullable();// nombre original
            $table->integer('evidencia_size')->nullable();        // tamaño en bytes
            $table->string('evidencia_mime_type')->nullable();    // tipo MIME

            // Aprobación nivel 1
            $table->enum('approval1_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approval1_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval1_comment')->nullable();
            $table->timestamp('approval1_at')->nullable();

            // Aprobación nivel 2
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
