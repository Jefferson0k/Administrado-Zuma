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

        Schema::create('companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('risk');
            $table->string('business_name');
            $table->foreignId('sector_id')->constrained()->onDelete('cascade');
            $table->foreignId('subsector_id')->nullable()->constrained()->onDelete('cascade');
            $table->year('incorporation_year')->nullable();
            $table->string('sales_volume')->nullable(); // Volumen de facturación
            $table->string('document', 11);
            $table->string('link_web_page');
            $table->longText('description')->nullable();
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
        Schema::dropIfExists('companies');
    }
};
