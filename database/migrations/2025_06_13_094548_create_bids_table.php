<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')
                ->nullable()
                ->constrained('auctions')
                ->nullOnDelete();
            $table->foreignId('solicitud_bid_id')
                ->nullable()
                ->constrained('solicitud_bids')
                ->nullOnDelete();
            $table->ulid('investors_id');
            $table->foreign('investors_id')
                ->references('id')  
                ->on('investors')
                ->onDelete('cascade');
            $table->enum('type', ['auction', 'solicitud'])->default('solicitud');
            $table->timestamps();
            $table->index(['auction_id', 'solicitud_bid_id', 'investors_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
