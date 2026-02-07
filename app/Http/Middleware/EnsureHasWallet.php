<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasWallet
{
    /**
     * Verifica che l'utente autenticato abbia un wallet configurato.
     * Usa questo middleware per le rotte che richiedono firma blockchain.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Non autenticato'], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->wallet_address || !$user->encrypted_private_key) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Wallet non configurato',
                    'code' => 'WALLET_NOT_CONFIGURED'
                ], 403);
            }
            // Redirect a pagina setup wallet se esiste
            return redirect()->route('login')->with('error', 'Configura il wallet per continuare');
        }

        return $next($request);
    }
}