<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items.product.seller', 'statusHistories.changedBy'])
            ->where('user_id', Auth::id());

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->get();

        return Inertia::render('Buyer/Orders', [
            'orders' => $orders,
            'filters' => $request->only(['status'])
        ]);
    }

    public function showCheckout()
    {
        $cartItems = CartItem::with(['product.seller'])
            ->where('user_id', Auth::id())
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Keranjang belanja Anda kosong.']);
        }

        return Inertia::render('Buyer/Checkout', [
            'cartItems' => $cartItems
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'shipping_address' => ['required', 'string'],
            'payment_method' => ['required', 'string', 'in:COD,Transfer Bank'],
        ]);

        $userId = Auth::id();
        $cartItems = CartItem::with('product')->where('user_id', $userId)->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')->withErrors(['cart' => 'Keranjang belanja Anda kosong.']);
        }

        try {
            DB::beginTransaction();

            $totalAmount = 0;

            // 1. Calculate total amount and check stock availability
            foreach ($cartItems as $item) {
                $product = $item->product;
                if ($product->stock < $item->quantity) {
                    throw new \Exception("Stok produk '{$product->name}' tidak mencukupi untuk jumlah yang Anda pesan.");
                }
                $totalAmount += $product->price * $item->quantity;
            }

            $status = $request->payment_method === 'Transfer Bank' ? 'unpaid' : 'pending';
            $note = $status === 'unpaid'
                ? 'Pesanan berhasil dibuat. Menunggu pembayaran transfer bank dari pembeli.'
                : 'Pesanan berhasil dibuat oleh pembeli.';

            // 2. Create Order
            $order = Order::create([
                'user_id' => $userId,
                'total_amount' => $totalAmount,
                'status' => $status,
                'shipping_address' => $request->shipping_address,
                'payment_method' => $request->payment_method,
            ]);

            // 3. Create OrderItems and decrement stock
            foreach ($cartItems as $item) {
                $product = $item->product;
                
                // Decrement stock
                $product->decrement('stock', $item->quantity);

                // Create OrderItem
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $item->quantity,
                ]);
            }

            // 4. Clear Cart
            CartItem::where('user_id', $userId)->delete();

            DB::commit();

            // Record initial status history
            OrderStatusHistory::create([
                'order_id'   => $order->id,
                'status'     => $status,
                'note'       => $note,
                'changed_by' => $userId,
            ]);

            // Optimization: Clear products catalog cache
            \Illuminate\Support\Facades\Cache::forget('products_all');

            // Optimization: Dispatch asynchronous background email job
            \App\Jobs\SendOrderConfirmation::dispatch($order);

            return redirect()->route('orders.index')->with('success', $status === 'unpaid' ? 'Checkout berhasil. Silakan unggah bukti pembayaran Anda.' : 'Checkout berhasil. Pesanan Anda sedang diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }
    }

    public function uploadPaymentProof(Request $request, Order $order)
    {
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        if ($order->status !== 'unpaid') {
            return back()->withErrors(['error' => 'Pesanan ini tidak membutuhkan bukti pembayaran atau sudah terbayar.']);
        }

        $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        if ($request->hasFile('payment_proof')) {
            $path = '/storage/' . $request->file('payment_proof')->store('payment_proofs', 'public');
            
            $order->update([
                'payment_proof_path' => $path,
                'status' => 'pending',
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'note' => 'Bukti pembayaran berhasil diunggah oleh pembeli. Menunggu konfirmasi dari penjual.',
                'changed_by' => Auth::id(),
            ]);

            return redirect()->route('orders.index')->with('success', 'Bukti pembayaran berhasil diunggah. Pesanan Anda kini sedang dikonfirmasi penjual.');
        }

        return back()->withErrors(['payment_proof' => 'Gagal mengunggah file bukti pembayaran.']);
    }
}
