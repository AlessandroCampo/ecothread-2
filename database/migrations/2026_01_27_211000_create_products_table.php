<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
     public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->string('id')->primary();
            
            // Dati off-chain (sempre presenti)
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('product_type', 50)->nullable();
            $table->unsignedSmallInteger('collection_year');
            $table->string('image_path')->nullable();
            $table->string('creator_wallet', 44); // Wallet di chi ha creato il draft
            
            // Dati on-chain (nullable fino alla conferma)
            $table->unsignedBigInteger('creation_timestamp')->nullable();
            $table->string('pda_address', 44)->nullable();
            $table->string('tx_signature', 88)->nullable();
            
            // Stato del prodotto
            $table->string('status', 20)->default('draft'); // draft | active
            $table->boolean('is_on_chain')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index('product_type');
            $table->index('collection_year');
            $table->index('creator_wallet');
            $table->index('status');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
