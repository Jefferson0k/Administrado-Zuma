<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('property_investors', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('config_id')->constrained('property_configuracions')->onDelete('cascade');
            $table->foreignUlid('investor_id')->nullable()->constrained('investors')->onDelete('cascade');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('status')->default('pendiente');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('property_investors');
    }
};
