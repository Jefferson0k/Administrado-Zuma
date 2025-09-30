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
        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tipo_inmueble')->after('id')->nullable();

            $table->foreign('id_tipo_inmueble')
                ->references('id_tipo_inmueble')
                ->on('tipo_inmueble')
                ->onDelete('set null'); // si se borra el tipo, el inmueble queda null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_inmueble']);
            $table->dropColumn('id_tipo_inmueble');
        });
    }
};
