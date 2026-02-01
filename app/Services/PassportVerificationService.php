<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Event;
use App\Models\Passport;
use App\Enums\EventType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PassportVerificationService
{
    public function __construct(
        private PinataService $pinata
    ) {}

    /**
     * Verifica se un prodotto soddisfa i requisiti per il passaporto
     */
    public function verifyProduct(Product $product): array
    {
        $result = [
            'eligible' => false,
            'product_id' => $product->id,
            'checks' => [
                'is_on_chain' => false,
                'required_events' => [],
                'documents' => [],
            ],
            'missing_events' => [],
            'errors' => [],
        ];

        // 1. Verifica che il prodotto sia on-chain
        if (!$product->is_on_chain || !$product->pda_address) {
            $result['errors'][] = 'Prodotto non registrato on-chain';
            return $result;
        }
        $result['checks']['is_on_chain'] = true;

        // 2. Verifica eventi obbligatori
        $requiredTypes = EventType::required();
        $events = $product->events()->where('is_on_chain', true)->get();
        
        foreach ($requiredTypes as $requiredType) {
            $event = $events->firstWhere('event_type', $requiredType->value);
            
            if (!$event) {
                $result['missing_events'][] = $requiredType->value;
                $result['checks']['required_events'][$requiredType->value] = [
                    'present' => false,
                    'on_chain' => false,
                ];
            } else {
                $result['checks']['required_events'][$requiredType->value] = [
                    'present' => true,
                    'on_chain' => (bool) $event->is_on_chain,
                    'event_id' => $event->id,
                    'pda_address' => $event->pda_address,
                ];
            }
        }

        // 3. Verifica documenti (se presenti)
        foreach ($events as $event) {
            if ($event->document_uri && $event->document_hash) {
                $docVerification = $this->verifyDocument($event);
                $result['checks']['documents'][$event->id] = $docVerification;
                
                if (!$docVerification['valid']) {
                    $result['errors'][] = "Documento evento {$event->event_type} non valido";
                }
            }
        }

        // 4. Determina eleggibilità
        $result['eligible'] = empty($result['missing_events']) && empty($result['errors']);

        return $result;
    }

    /**
     * Verifica un singolo documento
     */
    public function verifyDocument(Event $event): array
    {
        $result = [
            'valid' => false,
            'event_id' => $event->id,
            'document_uri' => $event->document_uri,
            'expected_hash' => $event->document_hash,
            'calculated_hash' => null,
            'hash_match' => false,
            'file_accessible' => false,
            'error' => null,
            'verified_at' => now()->toIso8601String(),
        ];

        if (!$event->document_uri) {
            $result['error'] = 'Nessun URI documento';
            return $result;
        }

        if (!$event->document_hash) {
            $result['error'] = 'Nessun hash documento registrato';
            return $result;
        }

        try {
            // Converti IPFS URI in gateway URL
            $gatewayUrl = $this->ipfsToGateway($event->document_uri);
            // Scarica il file
            $response = Http::withoutVerifying()
                ->timeout(30)
                ->get($gatewayUrl);


            if (!$response->successful()) {
                $result['error'] = "File non accessibile (HTTP {$response->status()})";
                return $result;
            }

            $result['file_accessible'] = true;

            // Calcola hash del contenuto scaricato
            $content = $response->body();
            $calculatedHash = hash('sha256', $content);
            $result['calculated_hash'] = $calculatedHash;

            // Confronta hash
            $result['hash_match'] = strtolower($calculatedHash) === strtolower($event->document_hash);
            
            if (!$result['hash_match']) {
                $result['error'] = 'Hash non corrisponde - documento potrebbe essere stato alterato';
                return $result;
            }

            $result['valid'] = true;

        } catch (\Exception $e) {
            Log::error('Document verification failed', [
                'event_id' => $event->id,
                'uri' => $event->document_uri,
                'error' => $e->getMessage(),
            ]);
            $result['error'] = 'Errore durante la verifica: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Converte URI IPFS in URL gateway
     */
   public function ipfsToGateway(string $uri): string
        {
            // Rimuovi il prefisso ipfs:// se presente
            $cid = $uri;
            if (str_starts_with($uri, 'ipfs://')) {
                $cid = substr($uri, 7);
            }
            
            $gateway = config('services.pinata.gateway', 'https://gateway.pinata.cloud');
            
            return "{$gateway}/ipfs/{$cid}";
        }

    /**
     * Rilascia un passaporto per un prodotto verificato
     */
    public function issuePassport(Product $product, string $requestedByWallet): Passport|array
    {
        // Verifica il prodotto
        $verification = $this->verifyProduct($product);

        if (!$verification['eligible']) {
            return [
                'success' => false,
                'verification' => $verification,
            ];
        }

        // Crea il passaporto
        $passport = Passport::create([
            'passport_number' => Passport::generatePassportNumber(),
            'product_id' => $product->id,
            'status' => Passport::STATUS_VERIFIED,
            'verification_result' => $verification,
            'verified_at' => now(),
            'requested_by_wallet' => $requestedByWallet,
            'verified_by' => 'system', // Verifica automatica
        ]);

        Log::info('Passport issued', [
            'passport_number' => $passport->passport_number,
            'product_id' => $product->id,
        ]);

        return $passport;
    }

    /**
     * Ri-verifica un passaporto esistente (per controlli periodici)
     */
    public function revalidatePassport(Passport $passport): array
    {
        $verification = $this->verifyProduct($passport->product);

        if (!$verification['eligible']) {
            $passport->update([
                'status' => Passport::STATUS_SUSPENDED,
                'verification_result' => $verification,
                'rejection_reason' => 'Verifica periodica fallita: ' . implode(', ', $verification['errors']),
            ]);
        }

        return $verification;
    }
}