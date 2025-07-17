<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fixed_term_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_entity_id')->constrained()->onDelete('cascade');
            $table->foreignId('amount_range_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('term_plan_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('rate_type_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('valor', 5, 2)->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('fixed_term_rates');
    }
};
