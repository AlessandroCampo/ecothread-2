<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;
use Laragear\WebAuthn\WebAuthnData;

class User extends Authenticatable implements WebAuthnAuthenticatable
{
    use WebAuthnAuthentication;

   protected $fillable = [
    'wallet_address',
    'name',
    'email',
    'website',
    'logo_path',
    'logo_url',
     'encrypted_private_key',
        'encryption_salt',
        'recovery_phrase_confirmed',
        'wallet_created_at',
];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'encrypted_private_key',
        'encryption_salt',
    ];

    protected $appends = ['logo_url'];

    protected function logoUrl(): Attribute
    {
        return Attribute::get(function () {
            if (!$this->logo_path) {
                return null;
            }
            return Storage::url($this->logo_path);

        });
    }

     public function webAuthnData(): WebAuthnData
    {
        return new WebAuthnData(
            name: $this->wallet_address,           // Identificatore univoco
            displayName: $this->name ?? 'Utente',  // Nome visualizzato
        );
    }

    public function hasWallet(): bool
    {
        return !empty($this->wallet_address) && !empty($this->encrypted_private_key);
    }
}