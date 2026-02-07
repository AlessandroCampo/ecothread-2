<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aggiungi campi per embedded wallet alla tabella users esistente
        Schema::table('users', function (Blueprint $table) {
            
            // Private key criptata con passkey (AES-256-GCM)
            // Formato: base64(IV + ciphertext)
            $table->text('encrypted_private_key')->nullable()->after('wallet_address');
            
            // Salt per derivare la chiave di encryption (unico per utente)
            $table->string('encryption_salt')->nullable()->after('encrypted_private_key');
            
            // Recovery phrase confermata
            $table->boolean('recovery_phrase_confirmed')->default(false)->after('encryption_salt');
            
            // Quando è stato creato il wallet
            $table->timestamp('wallet_created_at')->nullable()->after('recovery_phrase_confirmed');
        });

        // Crea tabella per le credenziali passkey
        Schema::create('passkey_credentials', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            
            // WebAuthn credential ID (base64url encoded)
            $table->string('credential_id', 512);
            
            // Public key della passkey (COSE format, base64)
            $table->string('public_key');
            
            // Contatore anti-replay (incrementa ad ogni uso)
            $table->unsignedBigInteger('counter')->default(0);
            
            // Tipo di authenticator
            $table->string('authenticator_type')->default('platform');
            
            // Nome dispositivo per UI
            $table->string('device_name')->nullable();
            
            // Transports supportati
            $table->json('transports')->nullable();
            
            // Ultimo utilizzo
            $table->timestamp('last_used_at')->nullable();
            
            $table->timestamps();
            
            // Index per lookup veloce
            $table->index('credential_id', 'idx_passkey_credential_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkey_credentials');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'encrypted_private_key',
                'encryption_salt',
                'recovery_phrase_confirmed',
                'wallet_created_at',
            ]);
        });
    }
};