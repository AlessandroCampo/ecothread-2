<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuthControllerOld extends Controller
{
    public function login()
    {
        return Inertia::render('Auth/Login');
    }

    public function verify(Request $request)
{
    $nonce = cache()->get('auth_nonce_' . $request->wallet);
    
    if (!$nonce) {
        return response()->json(['error' => 'Challenge expired'], 401);
    }
    
    if ($this->verifySignature($request->wallet, $nonce, $request->signature)) {
        cache()->forget('auth_nonce_' . $request->wallet);  // One-time use
        
        $user = User::firstOrCreate(['wallet_address' => $request->wallet]);
        $user->update(['last_login_at' => now()]);
        
        Auth::login($user, remember: true);  // 👈 Sessione persistente
        
        return response()->json([
            'success' => true,
            'redirect' => route('admin.dashboard'),
        ]);
    }
    
    return response()->json(['error' => 'Invalid signature'], 401);
}


public function challenge(Request $request)
{
    $nonce = 'ecothread_' . Str::random(32);
    
    // Salva temporaneamente (cache o session)
    cache()->put('auth_nonce_' . $request->wallet, $nonce, now()->addMinutes(5));
    
    return response()->json(['nonce' => $nonce]);
}

  private function verifySignature(string $wallet, string $message, array $signature): bool
    {
        try {
            // Decodifica wallet address da Base58
            $publicKey = $this->base58Decode($wallet);
            
            // Converti signature array in bytes
            $signatureBytes = pack('C*', ...$signature);
            
            // Verifica con Ed25519
            return sodium_crypto_sign_verify_detached(
                $signatureBytes,
                $message,
                $publicKey
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    private function base58Decode(string $input): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $indexes = array_flip(str_split($alphabet));

        $bytes = [0];
        for ($i = 0; $i < strlen($input); $i++) {
            $carry = $indexes[$input[$i]] ?? 0;
            for ($j = count($bytes) - 1; $j >= 0; $j--) {
                $carry += $bytes[$j] * 58;
                $bytes[$j] = $carry % 256;
                $carry = intdiv($carry, 256);
            }
            while ($carry > 0) {
                array_unshift($bytes, $carry % 256);
                $carry = intdiv($carry, 256);
            }
        }

        // Add leading zeros
        for ($i = 0; $i < strlen($input) && $input[$i] === '1'; $i++) {
            array_unshift($bytes, 0);
        }

        return pack('C*', ...$bytes);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        return response()->json(['success' => true]);
    }
}
