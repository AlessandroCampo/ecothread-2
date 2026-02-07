<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class SolanaService
{
    private string $rpcEndpoint;
    private string $feePayerPublicKey;
    private string $feePayerSecretKey; // 64 bytes raw

    private function httpClient()
    {
           $client = Http::timeout(30);  
        
        if (app()->environment('local', 'development')) {
            $client = $client->withoutVerifying();
        }
        
        return $client;
    }

    public function __construct()
    {
        $this->rpcEndpoint = config('solana.rpc_endpoint', 'https://api.devnet.solana.com');
        
        $privateKeyBase58 = config('solana.fee_payer_private_key');
        if (!$privateKeyBase58) {
            throw new Exception('SOLANA_FEE_PAYER_PRIVATE_KEY not configured');
        }

        // Decode base58 private key (64 bytes: 32 secret + 32 public)
        $this->feePayerSecretKey = $this->base58Decode($privateKeyBase58);
        
        if (strlen($this->feePayerSecretKey) !== 64) {
            throw new Exception('Invalid fee payer private key length');
        }

        // Public key is last 32 bytes
        $this->feePayerPublicKey = substr($this->feePayerSecretKey, 32, 32);
    }

    /**
     * Get fee payer info
     */
    public function getFeePayerInfo(): array
    {
        $publicKeyBase58 = $this->base58Encode($this->feePayerPublicKey);
        
        try {
            $balance = $this->getBalance($publicKeyBase58);
        } catch (Exception $e) {
            $balance = null;
        }

        return [
            'publicKey' => $publicKeyBase58,
            'balance' => $balance,
        ];
    }

    /**
     * Sign transaction with fee payer and submit to Solana
     */
    public function signAndSubmit(
        string $transactionBase64,
        string $signerPublicKey,
        string $signerSignatureBase64
    ): array {
        // Decode inputs
        $transactionBytes = base64_decode($transactionBase64);
        $signerSignature = base64_decode($signerSignatureBase64);
        $signerPubkeyBytes = $this->base58Decode($signerPublicKey);

        if (strlen($signerSignature) !== 64) {
            throw new Exception('Invalid signature length');
        }

        if (strlen($signerPubkeyBytes) !== 32) {
            throw new Exception('Invalid public key length');
        }

        


        // Parse transaction to extract message
        // Transaction format: [num_signatures, ...signatures (64 bytes each), message]
        $numSignatures = ord($transactionBytes[0]);
        $signaturesLength = $numSignatures * 64;
        $messageStart = 1 + $signaturesLength;
        $message = substr($transactionBytes, $messageStart);

        // Verify signer's signature
        $isValid = sodium_crypto_sign_verify_detached(
            $signerSignature,
            $message,
            $signerPubkeyBytes
        );

        if (!$isValid) {
            throw new Exception('Invalid signer signature');
        }


        // Sign with fee payer
        $feePayerSignature = sodium_crypto_sign_detached($message, $this->feePayerSecretKey);


        // Build signed transaction
        // Format: [num_signatures(2), fee_payer_sig(64), signer_sig(64), message]
        $signedTx = chr(2) . $feePayerSignature . $signerSignature . $message;

  
        // Send to Solana
        $txSignature = $this->sendTransaction(base64_encode($signedTx));



        return [
            'success' => true,
            'txSignature' => $txSignature,
        ];
    }

    /**
     * Send raw transaction to Solana RPC
     */
    private function sendTransaction(string $transactionBase64): string
    {


  
        $response =  $this->httpClient()->post($this->rpcEndpoint, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'sendTransaction',
            'params' => [
                $transactionBase64,
                [
                    'encoding' => 'base64',
                    'skipPreflight' => false,
                    'preflightCommitment' => 'confirmed',
                ],
            ],
        ]);


        $data = $response->json();

        if (isset($data['error'])) {
            Log::error('Solana RPC error', $data['error']);
            throw new Exception($data['error']['message'] ?? 'RPC Error');
        }

        return $data['result'];
    }

    /**
     * Get account balance
     */
  public function getBalance(string $publicKeyBase58): float
{
    $response = $this->httpClient()->post($this->rpcEndpoint, [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'getBalance',
        'params' => [$publicKeyBase58],
    ]);

    $data = $response->json();

    if (isset($data['error'])) {
        throw new Exception($data['error']['message'] ?? 'RPC Error');
    }

    return ($data['result']['value'] ?? 0) / 1e9;
}
    /**
     * Get latest blockhash
     */
    public function getLatestBlockhash(): array
    {
        $response = Http::post($this->rpcEndpoint, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getLatestBlockhash',
            'params' => [['commitment' => 'confirmed']],
        ]);

        $data = $response->json();

        if (isset($data['error'])) {
            throw new Exception($data['error']['message'] ?? 'RPC Error');
        }

        return $data['result']['value'];
    }

    /**
     * Base58 decode (Bitcoin alphabet)
     */
    private function base58Decode(string $input): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);

        $num = gmp_init(0);
        for ($i = 0; $i < strlen($input); $i++) {
            $pos = strpos($alphabet, $input[$i]);
            if ($pos === false) {
                throw new Exception('Invalid base58 character');
            }
            $num = gmp_add(gmp_mul($num, $base), $pos);
        }

        $hex = gmp_strval($num, 16);
        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        // Handle leading zeros
        $leadingZeros = 0;
        for ($i = 0; $i < strlen($input) && $input[$i] === '1'; $i++) {
            $leadingZeros++;
        }

        return str_repeat("\x00", $leadingZeros) . hex2bin($hex);
    }

    /**
     * Base58 encode (Bitcoin alphabet)
     */
    private function base58Encode(string $input): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);

        // Count leading zeros
        $leadingZeros = 0;
        for ($i = 0; $i < strlen($input) && $input[$i] === "\x00"; $i++) {
            $leadingZeros++;
        }

        $num = gmp_init(bin2hex($input), 16);
        $encoded = '';

        while (gmp_cmp($num, 0) > 0) {
            list($num, $remainder) = gmp_div_qr($num, $base);
            $encoded = $alphabet[gmp_intval($remainder)] . $encoded;
        }

        return str_repeat('1', $leadingZeros) . $encoded;
    }
}
