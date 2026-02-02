<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard(Request $request)
    {
        $query = Product::where('creator_wallet', Auth::user()->wallet_address)
            ->with(['events', 'passport']);

        // Filtro per stato on-chain
        if ($request->filled('status')) {
            $query->where('is_on_chain', $request->status === 'on-chain');
        }

        // Filtro per tipo prodotto
        if ($request->filled('collection_year')) {
            $query->where('collection_year', $request->collection_year);
        }

        // Ricerca per nome/id
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('id', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()->paginate(15)->through(function ($product) {
            $product->passport_progress = $this->calculatePassportProgress($product);
            return $product;
        });

        return Inertia::render('Admin/Dashboard', [
            'products' => $products,
            'productTypes' => ProductType::toArray(),
            'filters' => $request->only(['status', 'product_type', 'search']),
        ]);
    }

    private function calculatePassportProgress(Product $product): array
    {
        $required = ['ORIGIN', 'PRODUCTION', 'TRANSPORT', 'ENV_CLAIM'];
        $events = $product->events->where('is_on_chain', true);

        $completed = array_values(array_filter($required, fn($t) => $events->contains('event_type', $t)));
        $missing = array_values(array_diff($required, $completed));

        return [
            'completed' => $completed,
            'missing' => $missing,
            'count' => count($completed),
            'total' => count($required),
            'eligible' => empty($missing) && $product->is_on_chain,
            'has_passport' => $product->passport !== null,
        ];
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