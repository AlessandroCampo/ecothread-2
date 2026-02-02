<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuthController extends Controller
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
        $base = '58';

        $num = '0';
        for ($i = 0; $i < strlen($input); $i++) {
            $num = bcadd(bcmul($num, $base), (string) strpos($alphabet, $input[$i]));
        }

        $hex = '';
        while (bccomp($num, '0') > 0) {
            $remainder = bcmod($num, '16');
            $hex = dechex((int) $remainder) . $hex;
            $num = bcdiv($num, '16', 0);
        }

        if (strlen($hex) % 2 !== 0) {
            $hex = '0' . $hex;
        }

        if ($hex === '') {
            $hex = '00';
        }

        return hex2bin($hex);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        return response()->json(['success' => true]);
    }
}
