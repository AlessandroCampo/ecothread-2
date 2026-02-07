<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SolanaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class SolanaController extends Controller
{
    public function __construct(
        private SolanaService $solanaService
    ) {}

    /**
     * Get fee payer public key and balance
     * GET /api/solana/fee-payer
     */
    public function feePayer(): JsonResponse
    {
        try {
            $info = $this->solanaService->getFeePayerInfo();
            return response()->json($info);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sign transaction with fee payer and submit
     * POST /api/solana/sign-and-submit
     */
    public function signAndSubmit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transaction' => 'required|string',
            'signerPublicKey' => 'required|string|min:32|max:44',
            'signerSignature' => 'required|string',
        ]);


        try {
            $result = $this->solanaService->signAndSubmit(
                $validated['transaction'],
                $validated['signerPublicKey'],
                $validated['signerSignature']
            );

            return response()->json($result);
        } catch (Exception $e) {
            dd($e->getMessage(), $e);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get latest blockhash
     * GET /api/solana/blockhash
     */
    public function blockhash(): JsonResponse
    {
        try {
            $blockhash = $this->solanaService->getLatestBlockhash();
            return response()->json($blockhash);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Health check
     * GET /api/solana/health
     */
    public function health(): JsonResponse
    {
        try {
            $info = $this->solanaService->getFeePayerInfo();
            dd($info);
            return response()->json([
                'status' => 'ok',
                'feePayer' => $info['publicKey'],
                'balance' => $info['balance'],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
