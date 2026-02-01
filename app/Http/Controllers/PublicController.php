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
        
        // Cerca per passport number
        $passport = Passport::where('passport_number', 'LIKE', "%{$query}%")->first();
        
        if ($passport) {
            return response()->json([
                'success' => true,
                'url' => route('passport.verify', $passport->passport_number)
            ]);
        }
        
        // Cerca per product ID
        $product = Product::where('id', $query)
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->first();

            
        if ($product) {
            return response()->json([
                'success' => true,
                'url' => route('passport.verify', $product->id)
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Nessun prodotto trovato con questo codice'
        ]);
    }
}