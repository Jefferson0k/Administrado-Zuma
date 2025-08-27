<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('risk');
            $table->string('business_name');
            $table->foreignId('sector_id')->constrained()->onDelete('cascade');
            $table->foreignId('subsector_id')->nullable()->constrained()->onDelete('cascade');
            $table->year('incorporation_year')->nullable();
            $table->decimal('sales_volume', 15, 2)->nullable();
            $table->string('document', 11)->unique();
            $table->string('link_web_page');
            $table->longText('description')->nullable();

            $table->enum('moneda', ['USD', 'PEN', 'BOTH'])->default('PEN');

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('companies');
    }
};
