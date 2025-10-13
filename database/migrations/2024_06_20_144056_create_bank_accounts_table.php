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
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('bank_id')->constrained('banks');
            $table->string('type');
            $table->char('currency', 3);
            $table->string('cc');
            $table->string('cci');
            $table->string('alias')->nullable();
            $table->enum('status0', ['pending', 'observed', 'approved', 'rejected'])
                ->default('pending');
            $table->enum('status', ['pending', 'observed', 'approved', 'rejected'])
                ->default('pending');

            $table->enum('status_conclusion', ['pending', 'approved', 'rejected', 'deleted'])
                ->default('pending');


            $table->foreignUlid('investor_id')->constrained();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('comment0')->nullable();
            $table->text('comment')->nullable();

            $table->foreignId('updated0_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('updated0_at')->nullable();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('updated_last_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
