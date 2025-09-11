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
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');
        
        Schema::connection($connection)->create($table, function (Blueprint $table) {
            $morphPrefix = config('audit.user.morph_prefix', 'user');
            
            $table->bigIncrements('id');
            $table->string($morphPrefix . '_type')->nullable();
            $table->unsignedBigInteger($morphPrefix . '_id')->nullable();
            $table->string('event');
            
            // 🔹 En lugar de usar morphs(), crear las columnas manualmente
            $table->string('auditable_type');
            $table->string('auditable_id', 36); // 36 caracteres para ULIDs
            
            $table->longText('old_values')->nullable(); // 🔹 LONGTEXT para JSONs grandes
            $table->longText('new_values')->nullable(); // 🔹 LONGTEXT para JSONs grandes
            $table->text('url')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 1023)->nullable();
            $table->string('tags')->nullable();
            $table->timestamps();
            
            // 🔹 Índices
            $table->index([$morphPrefix . '_id', $morphPrefix . '_type']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');
        
        Schema::connection($connection)->dropIfExists($table);
    }
};