<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = CartItem::with(['product.seller'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return Inertia::render('Buyer/Cart', [
            'cartItems' => $cartItems
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $productId = $request->product_id;
        $quantity = $request->quantity;
        $userId = Auth::id();

        // Check product stock
        $product = Product::findOrFail($productId);
        if ($product->stock < $quantity) {
            return back()->withErrors(['quantity' => 'Stok produk tidak mencukupi.']);
        }

        // Check if item already exists in cart
        $cartItem = CartItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $quantity;
            if ($product->stock < $newQuantity) {
                return back()->withErrors(['quantity' => 'Total kuantitas melebihi stok yang tersedia.']);
            }
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            CartItem::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        return redirect()->route('cart.index')->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $quantity = $request->quantity;

        // Check product stock
        if ($cartItem->product->stock < $quantity) {
            return back()->withErrors(['quantity' => 'Stok produk tidak mencukupi.']);
        }

        $cartItem->update(['quantity' => $quantity]);

        return redirect()->route('cart.index')->with('success', 'Keranjang diperbarui.');
    }

    public function destroy(CartItem $cartItem)
    {
        if ($cartItem->user_id !== Auth::id()) {
            abort(403);
        }

        $cartItem->delete();

        return redirect()->route('cart.index')->with('success', 'Produk dihapus dari keranjang.');
    }
}
