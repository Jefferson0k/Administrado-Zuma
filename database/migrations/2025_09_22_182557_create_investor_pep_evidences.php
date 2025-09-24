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
        Schema::create('investor_pep_evidences', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();

            // FK — matches investors.id (bigint unsigned)
            $table->foreignIdFor(\App\Models\Investor::class)
                  ->constrained()
                  ->cascadeOnDelete();

            $table->string('file_path');               // S3 object key
            $table->string('storage_disk')->default('s3');
            $table->string('original_name')->nullable();
            $table->string('extension', 16)->nullable();
            $table->string('mime', 191)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('status', 32)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_pep_evidences');
    }
};
