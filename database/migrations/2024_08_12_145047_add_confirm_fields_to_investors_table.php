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
            $table->boolean('is_pep')->default(false);
            $table->boolean('has_relationship_pep')->default(false);
            $table->string('department')->max(2)->nullable();
            $table->string('province')->max(2)->nullable();
            $table->string('district')->max(2)->nullable();
            $table->string('address')->max(255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            $table->dropColumn('is_pep');
            $table->dropColumn('has_relationship_pep');
            $table->dropColumn('department');
            $table->dropColumn('province');
            $table->dropColumn('district');
            $table->dropColumn('address');
        });
    }
};
