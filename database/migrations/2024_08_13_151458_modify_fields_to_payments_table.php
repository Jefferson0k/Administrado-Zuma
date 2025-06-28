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
        Schema::table('payments', function (Blueprint $table) {
            $table->date('reprogramation_date')->nullable()->change();
            $table->decimal('reprogramation_rate', 5, 2)->nullable()->change();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('status', ['inactive', 'active', 'expired', 'judicialized', 'reprogramed', 'paid', 'canceled'])->default('inactive')->change();
            $table->decimal('paid_amount', 10, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            //
        });
    }
};
