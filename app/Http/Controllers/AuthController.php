<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Laragear\WebAuthn\Http\Requests\AssertionRequest;
use Laragear\WebAuthn\Http\Requests\AssertedRequest;
use Laragear\WebAuthn\Attestation\Creator\AttestationCreation;
use Laragear\WebAuthn\Attestation\Creator\AttestationCreator;
use Laragear\WebAuthn\Attestation\Validator\AttestationValidation;
use Laragear\WebAuthn\Attestation\Validator\AttestationValidator;

class AuthController extends Controller
{
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    // REGISTRAZIONE
    public function registerOptions(Request $request, AttestationCreator $creator): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'wallet_address' => 'required|string|min:32|max:44|unique:users,wallet_address',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'wallet_address' => $validated['wallet_address'],
        ]);

        session(['webauthn_register_user_id' => $user->id]);

        $options = $creator
            ->send(new AttestationCreation($user))
            ->thenReturn()
            ->json;

        return response()->json($options);
    }

    public function registerVerify(Request $request, AttestationValidator $validator): JsonResponse
{

    Log::info('Register verify - raw request', [
            'all' => $request->all(),
        ]);
        
        // Decodifica clientDataJSON se presente
        $clientDataJSON = $request->input('response.clientDataJSON');
        if ($clientDataJSON) {
            $decoded = json_decode(base64_decode($clientDataJSON), true);
            Log::info('Register - WebAuthn origin', [
                'origin' => $decoded['origin'] ?? 'NOT FOUND',
            ]);
        }


    $validated = $request->validate([
        'encrypted_private_key' => 'required|string',
        'encryption_salt' => 'required|string',
    ]);

    $userId = session('webauthn_register_user_id');
    
    if (!$userId) {
        return response()->json(['error' => 'Sessione scaduta'], 400);
    }

    $user = User::findOrFail($userId);

    try {
        // Crea validazione con l'utente
        $validation = AttestationValidation::fromRequest($request);
        $validation->user = $user;

        $result = $validator
            ->send($validation)
            ->thenReturn();

        // Salva la credential
        $credential = $result->credential;
        $credential->authenticatable_id = $user->id;
        $credential->authenticatable_type = User::class;
        $credential->save();

        // Salva dati wallet
        $user->update([
            'encrypted_private_key' => $validated['encrypted_private_key'],
            'encryption_salt' => $validated['encryption_salt'],
            'wallet_created_at' => now(),
        ]);

        session()->forget('webauthn_register_user_id');
        Auth::login($user, true);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'wallet_address' => $user->wallet_address,
                'encrypted_private_key' => $user->encrypted_private_key,
                'encryption_salt' => $user->encryption_salt,
            ],
        ]);
    } catch (\Exception $e) {
        $user->delete();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

    // LOGIN
    public function loginOptions(AssertionRequest $request): JsonResponse
    {
        return response()->json($request->toVerify());
    }

    public function loginVerify(AssertedRequest $request): JsonResponse
    {
        $user = $request->login();

        if (!$user) {
            return response()->json(['error' => 'Passkey non riconosciuta'], 401);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'wallet_address' => $user->wallet_address,
                'encrypted_private_key' => $user->encrypted_private_key,
                'encryption_salt' => $user->encryption_salt,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['success' => true]);
    }

    public function checkSession(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['authenticated' => false], 401);
        }

        return response()->json([
            'authenticated' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'wallet_address' => $user->wallet_address,
                'encrypted_private_key' => $user->encrypted_private_key,
                'encryption_salt' => $user->encryption_salt,
            ],
        ]);
    }
}