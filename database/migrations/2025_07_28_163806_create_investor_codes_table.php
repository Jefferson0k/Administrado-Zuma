<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('investor_codes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->boolean('usado')->default(false);
            $table->foreignUlid('investor_id')->nullable()->constrained('investors')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_codes');
    }
};
