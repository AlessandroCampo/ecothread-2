<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Product;
use App\Services\PinataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function __construct(
        private PinataService $pinata
    ) {}

    public function uploadDocument(Request $request, string $productId)
    {
        $request->validate([
            'document' => 'required|file|max:10240',
            'event_type' => 'nullable|string',
        ]);

        $file = $request->file('document');

        $result = $this->pinata->uploadFile($file, [
            'productId' => $productId,
            'eventType' => $request->input('event_type'),
        ]);


        if ($result['success']) {
            return response()->json([
                'success' => true,
                'uri' => $result['uri'],
                'cid' => $result['cid'],
                'gateway_url' => $result['gateway_url'],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Upload failed',
        ], 500);
    }

    /**
     * Crea evento draft (prima della transazione on-chain)
     */
    public function store(Request $request, string $productId)
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'event_type' => 'required|string|exists:event_types,code',
            'trust_level' => 'nullable|string',
            'description' => 'nullable|string|max:1000',
            'document' => 'nullable|file|max:10240',
            'document_hash' => 'nullable|string|size:64',
            'document_uri' => 'nullable|string|max:128',
            'metadata' => 'nullable|json'
        ]);

         $metadata = [];
            if (!empty($validated['metadata'])) {
                $metadata = json_decode($validated['metadata'], true);
                $this->validateEventMetadata($validated['event_type'], $metadata);
            }

        $documentPath = null;
        $documentName = null;
            $documentMimeType = null; 


        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $documentName = $file->getClientOriginalName();
            $documentPath = $file->store("products/{$productId}/events", 'local');
$documentMimeType = $file->getMimeType(); 


        }

        $event = Event::create([
            'product_id' => $productId,
            'event_type' => $validated['event_type'],
            'trust_level' => $validated['trust_level'] ?? 'autodeclaration',
            'description' => $validated['description'] ?? null,
            'document_name' => $documentName,
            'document_path' => $documentPath,
            'document_hash' => $validated['document_hash'] ?? null,
            'document_uri' => $validated['document_uri'] ?? null,
            'registrant_wallet' => auth()->user()->wallet_address,
            'document_mime_type' => $documentMimeType ?? null,
            'status' => 'draft',
            'is_on_chain' => false,
                    'metadata' => $metadata,

        ]);

        return response()->json([
            'success' => true,
            'event' => $event,
        ], 201);
    }

    /**
     * Conferma evento dopo transazione on-chain
     */
    public function confirm(Request $request, int $id)
    {
        $event = Event::where('id', $id)
            ->where('status', 'draft')
            ->firstOrFail();

        $validated = $request->validate([
            'index' => 'required|integer|min:0',
            'timestamp' => 'required|integer',
            'pda_address' => 'required|string|max:44',
            'tx_signature' => 'required|string|max:88',
        ]);

        $event->update([
            'index' => $validated['index'],
            'timestamp' => $validated['timestamp'],
            'pda_address' => $validated['pda_address'],
            'tx_signature' => $validated['tx_signature'],
            'status' => 'confirmed',
            'is_on_chain' => true,
        ]);

        return response()->json([
            'success' => true,
            'event' => $event->fresh(),
        ]);
    }

    /**
     * Cancella evento draft (rollback)
     */
    public function destroy(int $id)
    {
        $event = Event::where('id', $id)
            ->where('status', 'draft')
            ->firstOrFail();

        // Rimuovi documento locale se presente
        if ($event->document_path) {
            Storage::disk('local')->delete($event->document_path);
        }

        $event->delete();

        return response()->json(['success' => true]);
    }
    /**
     * Download event document (from local backup).
     */
    public function downloadDocument(string $productId, int $index)
    {
        $event = Event::where('product_id', $productId)
            ->where('index', $index)
            ->firstOrFail();

        if (!$event->document_path || !Storage::disk('local')->exists($event->document_path)) {
            // If no local file, redirect to IPFS gateway
            if ($event->document_uri) {
                $cid = str_replace('ipfs://', '', $event->document_uri);
                return redirect($this->pinata->getGatewayUrl($cid));
            }
            
            return response()->json(['error' => 'Document not found'], 404);
        }

        return Storage::disk('local')->download(
            $event->document_path,
            $event->document_name
        );
    }

    private function validateEventMetadata(string $eventType, array $metadata): void
{
    $rules = match($eventType) {
        'ORIGIN' => [
            'country' => 'required|string|size:2',
            'region' => 'nullable|string|max:100',
            'compositions' => 'required|array|min:1',
            'compositions.*.material' => 'required|string',
            'compositions.*.percentage' => 'required|numeric|min:0|max:100',
            'compositions.*.certification' => 'nullable|string|max:50',
            'compositions.*.is_recycled' => 'nullable|boolean',
        ],
        'PRODUCTION' => [
            'processes' => 'required|array|min:1',
            'processes.*' => 'string',
            'water_usage_liters' => 'nullable|numeric|min:0',
            'energy_kwh' => 'nullable|numeric|min:0',
        ],
        'TRANSPORT' => [
            'origin_country' => 'required|string|size:2',
            'destination_country' => 'required|string|size:2',
            'transport_mode' => 'required|string',
            'distance_km' => 'nullable|numeric|min:0',
            'co2_kg' => 'nullable|numeric|min:0',
        ],
        'PACKAGING' => [
            'materials' => 'required|array|min:1',
            'materials.*' => 'string',
            'is_recyclable' => 'required|boolean',
        ],
        'RECYCLE' => [
            'recycle_percentage' => 'required|numeric|min:0|max:100',
            'take_back_program' => 'required|boolean',
        ],
        'CERTIFICATION' => [
            'certification_type' => 'required|string',
            'issued_by' => 'required|string|max:255',
            'valid_until' => 'nullable|date',
            'certificate_number' => 'nullable|string|max:100',
        ],
        default => [],
    };

    if (!empty($rules)) {
        $validator = validator($metadata, $rules);
        
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Validazione extra: composizione deve sommare a 100%
        if ($eventType === 'ORIGIN' && isset($metadata['compositions'])) {
            $total = array_sum(array_column($metadata['compositions'], 'percentage'));
            if (abs($total - 100) > 0.01) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'compositions' => ['La composizione totale deve essere 100% (attuale: ' . $total . '%)'],
                ]);
            }
        }
    }
}

}