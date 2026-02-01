<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passports', function (Blueprint $table) {
            $table->id();
            $table->string('passport_number')->unique(); // ECO-2026-00001
            $table->string('product_id');
            
            // Status del passaporto
            $table->enum('status', ['pending', 'verified', 'rejected', 'suspended'])->default('pending');
            
            // Risultati verifica
            $table->json('verification_result')->nullable(); // Dettagli della verifica
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Opzionale: scadenza annuale?
            
            // Chi ha richiesto/approvato
            $table->string('requested_by_wallet', 44);
            $table->string('verified_by')->nullable(); // 'system' o wallet di un admin
            
            // Motivo se rejected/suspended
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
            
            // Indexes
            $table->index('status');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passports');
    }
};