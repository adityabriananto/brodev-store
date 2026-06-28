<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        if (empty($search)) {
            $products = \Illuminate\Support\Facades\Cache::rememberForever('products_all', function () {
                return Product::with('seller')->latest()->get()->toArray();
            });
        } else {
            $products = Product::with('seller')
                ->where('name', 'like', "%{$search}%")
                ->latest()
                ->get();
        }

        return Inertia::render('Buyer/Home', [
            'products' => $products,
            'filters' => [
                'search' => $search,
            ]
        ]);
    }

    public function show(Product $product)
    {
        $product->load('seller');
        return Inertia::render('Buyer/ProductDetail', [
            'product' => $product
        ]);
    }

    public function sellerIndex()
    {
        $user = Auth::user();
        
        // Superadmin can see all products, sellers only see their own
        if ($user->role === 'superadmin') {
            $products = Product::with('seller')->latest()->get();
        } else {
            $products = Product::where('seller_id', $user->id)->latest()->get();
        }

        return Inertia::render('Seller/Products', [
            'products' => $products
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'], // Max 2MB
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move(public_path('images/uploads'), $imageName);
            $imagePath = '/images/uploads/' . $imageName;
        }

        Product::create([
            'seller_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'image_path' => $imagePath,
        ]);

        \Illuminate\Support\Facades\Cache::forget('products_all');

        return redirect()->route('seller.products')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        // Check authorization
        $user = Auth::user();
        if ($user->role !== 'superadmin' && $product->seller_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah produk ini.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $data = [
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
        ];

        if ($request->hasFile('image')) {
            // Delete old file if it exists in uploads
            if ($product->image_path && str_starts_with($product->image_path, '/images/uploads/')) {
                $oldPath = public_path(substr($product->image_path, 1));
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $imageName = time() . '_' . uniqid() . '.' . $request->image->extension();
            $request->image->move(public_path('images/uploads'), $imageName);
            $data['image_path'] = '/images/uploads/' . $imageName;
        }

        $product->update($data);

        \Illuminate\Support\Facades\Cache::forget('products_all');

        return redirect()->route('seller.products')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        // Check authorization
        $user = Auth::user();
        if ($user->role !== 'superadmin' && $product->seller_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus produk ini.');
        }

        // Delete image file if exists in uploads
        if ($product->image_path && str_starts_with($product->image_path, '/images/uploads/')) {
            $oldPath = public_path(substr($product->image_path, 1));
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $product->delete();

        \Illuminate\Support\Facades\Cache::forget('products_all');

        return redirect()->route('seller.products')->with('success', 'Produk berhasil dihapus.');
    }
}
