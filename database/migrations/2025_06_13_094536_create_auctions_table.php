<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();

            $table->foreignUlid('property_id')->constrained()->onDelete('cascade');

            $table->decimal('monto_inicial', 12, 2); 
            $table->date('dia_subasta');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->dateTime('tiempo_finalizacion');

            $table->enum('estado', ['pendiente', 'activa', 'finalizada'])->default('pendiente');

            $table->foreignUlid('ganador_id')->nullable()->constrained('investors')->nullOnDelete();

            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('auctions');
    }
};
