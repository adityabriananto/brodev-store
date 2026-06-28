<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SellerOrderController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();

        if ($user->role === 'superadmin') {
            $totalProducts = Product::count();
            $totalOrders = Order::count();
            $totalEarnings = Order::where('status', '!=', 'pending')->sum('total_amount');
            $recentOrders = Order::with('user')->latest()->take(5)->get();
        } else {
            $sellerId = $user->id;
            $totalProducts = Product::where('seller_id', $sellerId)->count();

            $totalOrders = Order::whereHas('items.product', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })->count();

            $totalEarnings = OrderItem::whereHas('product', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->whereHas('order', function ($q) {
                $q->where('status', '!=', 'pending');
            })
            ->selectRaw('SUM(price * quantity) as total')
            ->value('total') ?? 0;

            $recentOrders = Order::whereHas('items.product', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->with('user')
            ->latest()
            ->take(5)
            ->get();
        }

        return Inertia::render('Seller/Dashboard', [
            'stats' => [
                'totalProducts' => $totalProducts,
                'totalOrders'   => $totalOrders,
                'totalEarnings' => (float)$totalEarnings,
            ],
            'recentOrders' => $recentOrders
        ]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Order::query();

        if ($user->role !== 'superadmin') {
            $sellerId = $user->id;
            $query->whereHas('items.product', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            });
        }

        // Search by Order Number
        if ($request->filled('search_order_number')) {
            $query->where('order_number', 'like', '%' . $request->search_order_number . '%');
        }

        // Filter by Date Created
        if ($request->filled('created_date')) {
            $query->whereDate('created_at', $request->created_date);
        }

        // Filter by Date Updated
        if ($request->filled('updated_date')) {
            $query->whereDate('updated_at', $request->updated_date);
        }

        $orders = $query->with(['items.product.seller', 'user', 'statusHistories.changedBy'])
            ->latest()
            ->get();

        return Inertia::render('Seller/Orders', [
            'orders' => $orders,
            'filters' => $request->only(['search_order_number', 'created_date', 'updated_date'])
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,processed,shipped,delivered'],
            'tracking_number' => ['nullable', 'required_if:status,shipped', 'string', 'max:255'],
        ]);

        $user = Auth::user();

        // Check if seller owns at least one product in this order
        if ($user->role !== 'superadmin') {
            $ownsProduct = OrderItem::where('order_id', $order->id)
                ->whereHas('product', function ($q) use ($user) {
                    $q->where('seller_id', $user->id);
                })->exists();

            if (!$ownsProduct) {
                abort(403, 'Anda tidak memiliki akses untuk mengubah status pesanan ini.');
            }
        }

        $oldStatus = $order->status;
        $newStatus = $request->status;

        $updateData = ['status' => $newStatus];
        if ($request->has('tracking_number')) {
            $updateData['tracking_number'] = $request->tracking_number;
        }

        $order->update($updateData);

        // Record status change in history
        $statusLabels = [
            'unpaid'    => 'Menunggu Pembayaran',
            'pending'   => 'Menunggu Konfirmasi',
            'processed' => 'Diproses',
            'shipped'   => 'Dikirim',
            'delivered' => 'Diterima / Selesai',
        ];

        $note = 'Status diubah dari "' . ($statusLabels[$oldStatus] ?? $oldStatus) . '" menjadi "' . ($statusLabels[$newStatus] ?? $newStatus) . '".';
        if ($newStatus === 'shipped' && $request->tracking_number) {
            $note .= ' Nomor Resi: ' . $request->tracking_number . '.';
        }

        OrderStatusHistory::create([
            'order_id'   => $order->id,
            'status'     => $newStatus,
            'note'       => $note,
            'changed_by' => $user->id,
        ]);

        return redirect()->route('seller.orders')->with('success', 'Status pesanan berhasil diperbarui.');
    }
}
