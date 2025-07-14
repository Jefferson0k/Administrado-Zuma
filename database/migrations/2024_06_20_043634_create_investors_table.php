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
        Schema::create('investors', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('first_last_name');
            $table->string('second_last_name');
            $table->string('alias')->nullable();
            $table->char('document', 15)->comment('DNI, RUC, CARNET DE EXTRANJERIA, PASAPORTE');
            $table->string('email');
            $table->string('password');
            $table->string('telephone');
            $table->string('document_front')->nullable();
            $table->string('document_back')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->enum('status', ['not validated', 'validated'])->default('not validated');
            $table->timestamp('email_verified_at')->nullable()->comment('Fecha de verificación de email');

            $table->boolean('is_pep')->default(false)->commet('Persona politicamente expuesta');
            $table->boolean('has_relationship_pep')->default(false)->comment(('Tiene algun pariente o familiar que sea PEP'));
            $table->string('department')->max(2)->nullable();
            $table->string('province')->max(2)->nullable();
            $table->string('district')->max(2)->nullable();
            $table->string('address')->max(255)->nullable()->comment('Dirección de la persona');

            $table->string('api_token', 60)->unique()->nullable();
            $table->enum('type', ['inversionista', 'cliente', 'mixto'])->default('inversionista');
            $table->integer('asignado')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investors');
    }
};
