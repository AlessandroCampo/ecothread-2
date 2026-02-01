<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{


    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $user = $request->user();

        // Handle logo upload
        if ($request->hasFile('logo')) {
                if ($user->logo_path) {
                    Storage::disk('public')->delete($user->logo_path);
                }

                $user->logo_path = $request->file('logo')->store('logos', 'public');
            }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'website' => $validated['website'] ?? null,
        ]);

        $user->save();

        return back()->with('success', 'Profilo aggiornato con successo.');
    }
}