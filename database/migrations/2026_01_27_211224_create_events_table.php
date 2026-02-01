<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('product_id');
            
            // Dati off-chain (sempre presenti dal draft)
            $table->string('event_type');
            $table->string('trust_level')->default('autodeclaration');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('document_name')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_hash', 64)->nullable(); // SHA-256 hex
            $table->string('document_uri')->nullable(); // IPFS URI
            $table->string('document_mime_type')->nullable();
            $table->string('registrant_wallet', 44); // Wallet di chi ha creato il draft
            
            // Dati on-chain (nullable fino alla conferma)
            $table->unsignedInteger('index')->nullable(); // On-chain index
            $table->unsignedBigInteger('timestamp')->nullable(); // Blockchain timestamp
            $table->string('pda_address', 44)->nullable();
            $table->string('tx_signature', 88)->nullable();
            
            // Stato dell'evento
            $table->string('status', 20)->default('draft'); // draft | confirmed
            $table->boolean('is_on_chain')->default(false);
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
            
            $table->foreign('event_type')
                  ->references('code')
                  ->on('event_types');
            
            // Indexes
            $table->index('product_id');
            $table->index('event_type');
            $table->index('registrant_wallet');
            $table->index('status');
            
            // Unique solo per eventi confermati (index non null)
            // Lo gestiamo a livello applicativo
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};