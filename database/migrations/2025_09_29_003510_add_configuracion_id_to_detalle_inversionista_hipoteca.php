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
        Schema::table('detalle_inversionista_hipoteca', function (Blueprint $table) {
            $table->unsignedBigInteger('configuracion_id')->nullable()->after('id_detalle_inversionista_hipoteca');

            $table->foreign('configuracion_id')
                ->references('id')->on('property_configuracions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detalle_inversionista_hipoteca', function (Blueprint $table) {
            $table->dropForeign(['configuracion_id']);
            $table->dropColumn('configuracion_id');
        });
    }
};
