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
        Schema::table('investors', function (Blueprint $table) {
            $table->unsignedBigInteger('tipo_documento_id')->nullable()->after('alias');

            // índice explícito (buena práctica)
            $table->index('tipo_documento_id');

            // clave foránea con set null al eliminar
            $table->foreign('tipo_documento_id')
                  ->references('id_tipo_documento')
                  ->on('tipo_documento')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->dropForeign(['tipo_documento_id']);
            $table->dropIndex(['tipo_documento_id']);
            $table->dropColumn('tipo_documento_id');
        });
    }
};
