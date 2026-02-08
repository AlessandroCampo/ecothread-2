<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Relying Party
    |--------------------------------------------------------------------------
    |
    | Configurazione del Relying Party (la tua applicazione).
    | Il name viene mostrato all'utente durante la registrazione.
    | L'id deve corrispondere al dominio (senza porta).
    |
    */
    
    'relying_party' => [
        'name' => env('APP_NAME', 'EcoThread'),
        'id' => env('WEBAUTHN_ID', null), // null = usa il dominio corrente
    ],

    /*
    |--------------------------------------------------------------------------
    | Origins
    |--------------------------------------------------------------------------
    |
    | Origin aggiuntivi ammessi (es. app Android nativa).
    | Separati da virgola in WEBAUTHN_ORIGINS.
    |
    */

    'origins' => env('WEBAUTHN_ORIGINS') ? explode(',', env('WEBAUTHN_ORIGINS')) : null,

    /*
    |--------------------------------------------------------------------------
    | Challenge
    |--------------------------------------------------------------------------
    */
    
    'challenge' => [
        'bytes' => 32,
        'timeout' => 60, // secondi
        'key' => '_webauthn_challenge',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authenticator Selection
    |--------------------------------------------------------------------------
    |
    | Per passkey (discoverable credentials), impostiamo:
    | - residentKey: required (salva sul dispositivo)
    | - userVerification: required (richiede FaceID/TouchID/PIN)
    |
    */
    
    'authenticator_selection' => [
        'authenticator_attachment' => 'platform', // Solo passkey del dispositivo
        'resident_key' => 'required',
        'user_verification' => 'required',
    ],

    /*
    |--------------------------------------------------------------------------
    | Attestation Conveyance
    |--------------------------------------------------------------------------
    |
    | 'none' = non ci interessa quale hardware viene usato
    |
    */
    
    'attestation_conveyance' => 'none',

    /*
    |--------------------------------------------------------------------------
    | Public Key Credential Parameters
    |--------------------------------------------------------------------------
    */
    
    'public_key_credential_parameters' => [
        \Cose\Algorithm\Signature\ECDSA\ES256::ID,  // -7
        \Cose\Algorithm\Signature\RSA\RS256::ID,    // -257
    ],

    /*
    |--------------------------------------------------------------------------
    | Credentials Table
    |--------------------------------------------------------------------------
    */
    
    'database' => [
        'table' => 'webauthn_credentials',
        'morphs' => 'authenticatable',
    ],

    /*
    |--------------------------------------------------------------------------
    | Credential ID Storage
    |--------------------------------------------------------------------------
    |
    | Binary storage può essere più efficiente, ma base64 è più portabile.
    |
    */
    
    'credential_id' => [
        'binary' => false,
    ],
];