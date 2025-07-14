<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration{
    public function up(): void{
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->onDelete('cascade');

            $table->ulid('investors_id');
            $table->foreign('investors_id')->references('id')->on('investors')->onDelete('cascade');

            $table->decimal('monto', 12, 2);
            $table->timestamps();
        });

    }
    public function down(): void{
        Schema::dropIfExists('bids');
    }
};
