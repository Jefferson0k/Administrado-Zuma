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
        Schema::create('property_investor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignUlid('investor_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status')->default('pendiente');
            $table->timestamps();
            $table->unique(['property_id', 'investor_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_investor');
    }
};
