<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Solana RPC Endpoint
    |--------------------------------------------------------------------------
    */
    'rpc_endpoint' => env('SOLANA_RPC_ENDPOINT', 'https://api.devnet.solana.com'),

    /*
    |--------------------------------------------------------------------------
    | Program ID
    |--------------------------------------------------------------------------
    */
    'program_id' => env('SOLANA_PROGRAM_ID'),

    /*
    |--------------------------------------------------------------------------
    | Fee Payer (EcoThread wallet that pays transaction fees)
    |--------------------------------------------------------------------------
    */
    'fee_payer_public_key' => env('SOLANA_FEE_PAYER_PUBLIC_KEY'),
    'fee_payer_private_key' => env('SOLANA_FEE_PAYER_PRIVATE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Transaction Builder Service URL
    |--------------------------------------------------------------------------
    | URL of the Node.js service that handles transaction building and signing
    */
    'builder_url' => env('SOLANA_BUILDER_URL', 'http://localhost:3001'),
];
