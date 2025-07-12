<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void{
        Schema::create('property_investor', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('property_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('investor_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status')->default('pendiente');
            $table->enum('role', ['cliente', 'inversionista', 'mixto'])->default('inversionista');
            $table->timestamps();
            $table->unique(['property_id', 'investor_id', 'role']);
        });
    }
    public function down(): void{
        Schema::dropIfExists('property_investor');
    }
};
