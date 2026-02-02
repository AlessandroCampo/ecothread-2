<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Passport;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');
        
        if (!$query) {
            return response()->json([
                'success' => false,
                'message' => 'Inserisci un termine di ricerca'
            ]);
        }
        
        // Cerca per passport number direttamente
        $passport = Passport::where('passport_number', 'LIKE', "%{$query}%")->first();
        
        if ($passport) {
            return response()->json([
                'success' => true,
                'url' => route('passport.verify', $passport->passport_number)
            ]);
        }
        
        // Cerca prodotti che hanno un passaporto
        $passport = Passport::whereHas('product', function ($q) use ($query) {
            $q->where('id', $query)
              ->orWhere('name', 'LIKE', "%{$query}%");
        })->first();
        
        if ($passport) {
            return response()->json([
                'success' => true,
                'url' => route('passport.verify', $passport->passport_number)
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Nessun prodotto trovato con questo codice'
        ]);
    }
}