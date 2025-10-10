<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('property_configuracions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes')->onDelete('cascade');
            $table->foreignId('deadlines_id')->nullable()->constrained('deadlines')->onDelete('set null');
            $table->bigInteger('tea')->nullable();
            $table->bigInteger('tem')->nullable();
            $table->enum('tipo_cronograma', ['frances', 'americano','-'])->default('-');
            $table->enum('riesgo', ['A+', 'A', 'B', 'C', 'D', '-'])->default('-');
            $table->integer('estado');

            $table->enum('estado_conclusion', [
                'approved','rejected','observed','pendiente'
            ])->default('pendiente');
            $table->enum('approval1_status', ['approved','rejected','observed'])->nullable();
            $table->foreignId('approval1_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval1_comment')->nullable();
            $table->timestamp('approval1_at')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::dropIfExists('property_configuracions');
    }
};
