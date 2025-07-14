<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('term_plans', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->integer('dias_minimos')->default(0);
            $table->integer('dias_maximos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('term_plans');
    }
};
