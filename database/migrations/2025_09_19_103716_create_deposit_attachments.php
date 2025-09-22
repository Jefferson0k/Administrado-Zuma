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
        Schema::create('deposit_attachments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('deposit_id')->constrained('deposits')->onDelete('cascade');

            // File metadata
            $table->string('path', 2048);
            $table->string('name', 512)->nullable();
            $table->string('mime', 255)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            // Audit
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
           $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_attachments');
    }
};
