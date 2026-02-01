<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard()
    {
        $products = Product::where('creator_wallet', Auth::user()->wallet_address)
            ->with(['events', 'passport'])
            ->latest()
            ->get();


        
        return Inertia::render('Admin/Dashboard', [
            'products' => $products,
             'productTypes' => collect(ProductType::cases())->map(fn($t) => [
                        'value' => $t->value,
                        'label' => $t->label(),
                        'icon' => $t->icon(),
                    ]),
        ]);
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string|unique:products',
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        Product::create([
            'product_id' => $request->product_id,
            'name' => $request->name,
            'description' => $request->description,
            'company_wallet' => Auth::user()->wallet_address,
        ]);

        return back();
    }
}