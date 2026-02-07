<?php

namespace App\Http\Requests;

use Laragear\WebAuthn\Http\Requests\AttestationRequest;

class PublicAttestationRequest extends AttestationRequest
{
    public function authorize(): bool
    {
        return true; // Permetti sempre per registrazione pubblica
    }
}