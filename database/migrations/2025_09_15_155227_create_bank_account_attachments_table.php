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
        Schema::create('bank_account_attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('bank_account_id')
                ->constrained('bank_accounts')
                ->cascadeOnDelete(); // Si borras la cuenta, se borran adjuntos
            $table->string('original_name');     // nombre original del archivo
            $table->string('path');              // ruta en el disco (storage)
            $table->string('mime_type', 191)->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes

            // (opcional) quién subió
            $table->foreignId('uploaded_by')->nullable()->index(); // id de users si aplica

            // (opcional) metadatos extra
            $table->json('meta')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['bank_account_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_account_attachments');
    }
};
