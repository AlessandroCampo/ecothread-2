<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Enums\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::withCount('events')
            ->orderBy('created_at', 'desc')
            ->paginate(10);


        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Products/Create', [
            'productTypes' => collect(ProductType::cases())->map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'icon' => $t->icon(),
                'category' => $t->category(),
            ])->values(),
        ]);
    }

  public function store(Request $request)
{
    $validated = $request->validate([
        'id' => 'required|string|max:32|unique:products,id',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'url' => 'nullable|url',
        'product_type' => 'required|string',
        'collection_year' => 'nullable|integer',
        'image' => 'nullable|image|max:5120',
    ]);

    // Gestione immagine
    $imagePath = null;
    if ($request->hasFile('image')) {
        $imagePath = $request->file('image')->store('products', 'public');
    }

    $product = Product::create([
        'id' => $validated['id'],
        'name' => $validated['name'],
        'description' => $validated['description'] ?? null,
        'url' => $validated['url'] ?? null,
        'product_type' => $validated['product_type'],
        'collection_year' => $validated['collection_year'],
        'image_path' => $imagePath,
        'creator_wallet' => auth()->user()->wallet_address,
        'status' => 'draft',
        'is_on_chain' => false,
    ]);

    return response()->json([
        'success' => true,
        'product' => $product,
    ], 201);
}


public function update(Request $request, Product $product)
{
    $validated = $request->validate([
        'name' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'url' => 'nullable|url',
        'product_type' => ['required', new Enum(ProductType::class)],
        'collection_year' => 'nullable|integer',
        'image' => 'nullable|image|max:5120',
    ]);

    if ($request->hasFile('image')) {
        // Elimina vecchia immagine se esiste
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        
        $validated['image_path'] = $request->file('image')->store('products', 'public');
    }

    unset($validated['image']);

    $product->update($validated);

    return response()->json([
        'success' => true,
        'product' => $product->fresh(),
    ]);
}

public function confirm(Request $request, string $id)
{
    $product = Product::where('id', $id)
        ->where('status', 'draft')
        ->firstOrFail();

    $validated = $request->validate([
        'pda_address' => 'required|string|max:44',
        'tx_signature' => 'required|string|max:88',
        'creation_timestamp' => 'required|integer',
    ]);

    $product->update([
        'pda_address' => $validated['pda_address'],
        'tx_signature' => $validated['tx_signature'],
        'creation_timestamp' => $validated['creation_timestamp'],
        'status' => 'active',
        'is_on_chain' => true,
    ]);

    return response()->json([
        'success' => true,
        'product' => $product->fresh(),
    ]);
}

public function destroy(string $id)
{
    $product = Product::where('id', $id)
        ->where('status', 'draft') // Solo draft possono essere cancellati
        ->firstOrFail();

    // Rimuovi immagine se presente
    if ($product->image_path) {
        Storage::disk('public')->delete($product->image_path);
    }

    $product->delete();

    return response()->json(['success' => true]);
}

    public function show(Product $product)
    {
        $product->load('events.typeInfo');

        return Inertia::render('Admin/Products/Show', [
            'product' => $product,
        ]);
    }

}