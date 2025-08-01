<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void{
        Schema::create('investors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('first_last_name');
            $table->string('second_last_name');
            $table->char('document', 15)->comment('DNI, RUC, CARNET DE EXTRANJERIA, PASAPORTE');
            $table->string('alias')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('telephone')->nullable();
            $table->string('document_front')->nullable();
            $table->string('document_back')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->enum('status', ['not validated', 'validated'])->default('not validated');
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_pep')->default(false);
            $table->boolean('has_relationship_pep')->default(false);
            $table->string('department', 2)->nullable();
            $table->string('province', 2)->nullable();
            $table->string('district', 2)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('api_token', 60)->unique()->nullable();
            $table->string('codigo')->unique()->nullable()->comment('Código único del inversionista');
            $table->enum('type', ['inversionista', 'cliente', 'mixto'])->default('inversionista');
            $table->integer('asignado')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void{
        Schema::dropIfExists('investors');
    }
};
