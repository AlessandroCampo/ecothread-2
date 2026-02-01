<?php

namespace App\Http\Controllers;

use App\Models\Passport;
use App\Models\Product;
use App\Models\Event;
use App\Services\PassportVerificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PassportController extends Controller
{
    public function __construct(
        private PassportVerificationService $verificationService
    ) {}

    // =========================================================================
    // ADMIN ROUTES (autenticate)
    // =========================================================================

    /**
     * Lista passaporti per il brand autenticato
     */
    public function index(Request $request)
    {
        $passports = Passport::with('product')
            ->whereHas('product', function ($q) use ($request) {
                $q->where('creator_wallet', $request->user()->wallet_address);
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return Inertia::render('Admin/Passports/Index', [
            'passports' => $passports,
        ]);
    }

    /**
     * Mostra stato di eleggibilità per un prodotto
     */
    public function checkEligibility(Request $request, string $productId)
    {
        $product = Product::with('events')
            ->where('id', $productId)
            ->where('creator_wallet', $request->user()->wallet_address)
            ->firstOrFail();

        $verification = $this->verificationService->verifyProduct($product);

        return response()->json([
            'product_id' => $productId,
            'verification' => $verification,
        ]);
    }

    /**
     * Richiedi passaporto per un prodotto
     */
    public function request(Request $request, string $productId)
    {
        $product = Product::with('events')
            ->where('id', $productId)
            ->where('creator_wallet', $request->user()->wallet_address)
            ->firstOrFail();

        // Verifica che non esista già un passaporto valido
        $existingPassport = Passport::where('product_id', $productId)
            ->whereIn('status', [Passport::STATUS_PENDING, Passport::STATUS_VERIFIED])
            ->first();

        if ($existingPassport) {
            return response()->json([
                'success' => false,
                'error' => 'Esiste già un passaporto per questo prodotto',
                'passport' => $existingPassport,
            ], 409);
        }

        // Rilascia il passaporto
        $result = $this->verificationService->issuePassport(
            $product,
            $request->user()->wallet_address
        );

        if ($result instanceof Passport) {
            return response()->json([
                'success' => true,
                'passport' => $result->load('product'),
            ], 201);
        }

        // Verifica fallita
        return response()->json([
            'success' => false,
            'error' => 'Prodotto non idoneo per il passaporto',
            'verification' => $result['verification'],
        ], 422);
    }

    /**
     * Dettaglio passaporto (admin)
     */
    public function show(Request $request, int $id)
    {
        $passport = Passport::with('product.events')
            ->whereHas('product', function ($q) use ($request) {
                $q->where('creator_wallet', $request->user()->wallet_address);
            })
            ->findOrFail($id);

        return Inertia::render('Admin/Passports/Show', [
            'passport' => $passport,
        ]);
    }

    // =========================================================================
    // PUBLIC ROUTES (non autenticate)
    // =========================================================================

    /**
     * Pagina pubblica di verifica passaporto (da QR code)
     */
  // In PassportController.php - publicVerify già esiste
public function publicVerify(string $passportNumber)
{
    $passport = Passport::with(['product.events' => function ($q) {
        $q->where('is_on_chain', true)->orderBy('index');
    }])
    ->where('passport_number', $passportNumber)
    ->firstOrFail();


    return Inertia::render('Public/PassportVerify', [
        'passport' => $passport,
        'product' => $passport->product->load(['company']),
        'events' => $passport->product->events,
    ]);
}
    /**
     * API pubblica: verifica documento di un evento
     */
    public function verifyDocument(Request $request, int $eventId)
    {
        $event = Event::findOrFail($eventId);

        // Verifica che l'evento appartenga a un prodotto con passaporto valido
        $hasValidPassport = Passport::where('product_id', $event->product_id)
            ->where('status', Passport::STATUS_VERIFIED)
            ->exists();

        if (!$hasValidPassport) {
            return response()->json([
                'success' => false,
                'error' => 'Evento non appartiene a un prodotto con passaporto valido',
            ], 403);
        }

        $result = $this->verificationService->verifyDocument($event);

        return response()->json([
            'success' => $result['valid'],
            'verification' => $result,
        ]);
    }

    /**
     * API pubblica: stato passaporto (per widget esterni)
     */
    public function publicStatus(string $passportNumber)
    {
        $passport = Passport::with('product:id,name,product_type')
            ->where('passport_number', $passportNumber)
            ->first();

        if (!$passport) {
            return response()->json([
                'valid' => false,
                'error' => 'Passaporto non trovato',
            ], 404);
        }

        return response()->json([
            'valid' => $passport->isValid(),
            'passport_number' => $passport->passport_number,
            'status' => $passport->status,
            'product_name' => $passport->product->name,
            'product_type' => $passport->product->product_type,
            'verified_at' => $passport->verified_at?->toIso8601String(),
            'verification_url' => $passport->getVerificationUrl(),
        ]);
    }
}