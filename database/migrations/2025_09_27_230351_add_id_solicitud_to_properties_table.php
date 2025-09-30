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

            // Relación con solicitud
           $table->foreign('id_solicitud', 'properties_id_solicitud_foreign')
                  ->references('id_solicitud')
                  ->on('solicitud')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign('properties_id_solicitud_foreign');
        });
    }
};
