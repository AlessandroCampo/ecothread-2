<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PinataService
{
    private string $apiKey;
    private string $secretKey;
    private string $gateway;
    private string $baseUrl = 'https://api.pinata.cloud';

    public function __construct()
    {
        $this->apiKey = config('services.pinata.api_key');
        $this->secretKey = config('services.pinata.secret_key');
        $this->gateway = config('services.pinata.gateway', 'https://gateway.pinata.cloud');
    }

    /**
     * Upload a file to IPFS via Pinata.
     *
     * @param UploadedFile $file
     * @param array $metadata Optional metadata for the pin
     * @return array{success: bool, cid?: string, uri?: string, error?: string}
     */
    public function uploadFile(UploadedFile $file, array $metadata = []): array
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->secretKey,
            ])
            ->withoutVerifying()
            ->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )
            ->post("{$this->baseUrl}/pinning/pinFileToIPFS", [
                'pinataMetadata' => json_encode([
                    'name' => $file->getClientOriginalName(),
                    ...$metadata,
                ]),
                'pinataOptions' => json_encode([
                    'cidVersion' => 1,
                ]),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $cid = $data['IpfsHash'];

                Log::info('File uploaded to Pinata', [
                    'cid' => $cid,
                    'filename' => $file->getClientOriginalName(),
                ]);

                return [
                    'success' => true,
                    'cid' => $cid,
                    'uri' => "ipfs://{$cid}",
                    'gateway_url' => "{$this->gateway}/ipfs/{$cid}",
                    'mime_type' => $file->getMimeType(),  // ← Aggiungi
                    'file_name' => $file->getClientOriginalName(),
                ];
            }

            Log::error('Pinata upload failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Upload failed: ' . $response->body(),
            ];

        } catch (\Exception $e) {
            Log::error('Pinata upload exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Upload JSON metadata to IPFS.
     *
     * @param array $data
     * @param string $name
     * @return array{success: bool, cid?: string, uri?: string, error?: string}
     */
    public function uploadJson(array $data, string $name = 'metadata.json'): array
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])
            ->withoutVerifying()
            ->post("{$this->baseUrl}/pinning/pinJSONToIPFS", [
                'pinataContent' => $data,
                'pinataMetadata' => [
                    'name' => $name,
                ],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $cid = $responseData['IpfsHash'];

                return [
                    'success' => true,
                    'cid' => $cid,
                    'uri' => "ipfs://{$cid}",
                    'gateway_url' => "{$this->gateway}/ipfs/{$cid}",
                ];
            }

            return [
                'success' => false,
                'error' => 'Upload failed: ' . $response->body(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if a CID is pinned.
     *
     * @param string $cid
     * @return bool
     */
    public function isPinned(string $cid): bool
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->secretKey,
            ])
             ->withoutVerifying()
            ->get("{$this->baseUrl}/data/pinList", [
                'hashContains' => $cid,
                'status' => 'pinned',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return ($data['count'] ?? 0) > 0;
            }

            return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the public gateway URL for a CID.
     *
     * @param string $cid
     * @return string
     */
    public function getGatewayUrl(string $cid): string
    {
        return "{$this->gateway}/ipfs/{$cid}";
    }

    /**
     * Test API connection.
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders([
                'pinata_api_key' => $this->apiKey,
                'pinata_secret_api_key' => $this->secretKey,
            ])
             ->withoutVerifying()
            ->get("{$this->baseUrl}/data/testAuthentication");

            return $response->successful();

        } catch (\Exception $e) {
            return false;
        }
    }
}