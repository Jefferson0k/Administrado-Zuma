<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('amount_ranges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_entity_id')->constrained()->onDelete('cascade');
            $table->decimal('desde', 12, 2)->default(0);
            $table->decimal('hasta', 12, 2)->nullable(); // NULL = sin límite superior
            $table->enum('moneda', ['PEN', 'USD'])->default('PEN');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('amount_ranges');
    }
};
