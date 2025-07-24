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
         Schema::create('tbl_contact_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone', 20);
            $table->string('email');
            $table->string('interested_product');
            $table->text('message')->nullable();
            $table->enum('status', ['contact_us', 'internal'])->default('contact_us');
            $table->boolean('accepted_policy')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_contact_requests');
    }
};
