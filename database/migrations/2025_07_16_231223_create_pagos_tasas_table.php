<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void{
        Schema::create('pagos_tasas', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->integer('mes');
            $table->decimal('monto', 15, 2);
            $table->string('moneda', 3)->comment('Ejemplo: PEN, USD');
            $table->unsignedBigInteger('id_fixed_term_schedule');
            $table->foreign('id_fixed_term_schedule')
                  ->references('id')
                  ->on('fixed_term_schedules')
                  ->onDelete('cascade');
            $table->ulid('id_inversionista');
            $table->foreign('id_inversionista')
                  ->references('id')
                  ->on('investors')
                  ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_tasas');
    }
};
