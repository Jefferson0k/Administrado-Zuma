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
            $table->bigInteger('amount')->nullable();
            $table->string('status')->default('pendiente');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('property_investors');
    }
};
