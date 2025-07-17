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

        Schema::table('deposits', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
            $table->bigInteger('return')->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
            $table->bigInteger('financed_amount')->change();
            $table->bigInteger('financed_amount_by_garantia')->change();
            $table->bigInteger('paid_amount')->change();
        });

        Schema::table('movements', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
        });

        Schema::table('withdraws', function (Blueprint $table) {
            $table->bigInteger('amount')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::table('deposits', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });

        Schema::table('investments', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
            $table->decimal('financed_amount', 10, 2)->change();
            $table->decimal('financed_amount_by_garantia', 10, 2)->change();
            $table->decimal('paid_amount', 10, 2)->change();
        });

        Schema::table('movements', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });

        Schema::table('withdraws', function (Blueprint $table) {
            $table->decimal('amount', 10, 2)->change();
        });
    }
};
