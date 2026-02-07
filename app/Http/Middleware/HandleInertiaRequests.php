<?php

namespace App\Http\Middleware;

use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
            $user = FacadesAuth::user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
              'user' => $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'wallet_address' => $user->wallet_address,
            // Dati wallet - sicuri perché criptati e servono la passkey
            'encrypted_private_key' => $user->encrypted_private_key,
            'encryption_salt' => $user->encryption_salt,
        ] : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
