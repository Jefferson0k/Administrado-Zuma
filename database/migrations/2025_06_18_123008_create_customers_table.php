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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('first_last_name');
            $table->string('second_last_name');
            $table->string('alias')->nullable();
            $table->char('document', 8);
            $table->string('email');
            $table->string('password');
            $table->string('telephone');
            $table->string('document_front')->nullable();
            $table->string('document_back')->nullable();
            $table->enum('status', ['not validated', 'validated'])->default('not validated');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
