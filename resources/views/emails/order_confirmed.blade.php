<!DOCTYPE html>
<html>
<head>
    <title>Konfirmasi Pesanan Brodev - Online Store</title>
</head>
<body style="font-family: sans-serif; color: #333; line-height: 1.5; padding: 10px;">
    <h2>Halo {{ $order->user->name }},</h2>
    <p>Terima kasih telah berbelanja di <strong>Brodev - Online Store</strong>. Pesanan Anda telah berhasil dibuat!</p>
    
    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; margin: 20px 0; background-color: #f9f9f9;">
        <h3 style="margin-top: 0;">Detail Pesanan #{{ $order->id }}</h3>
        <p><strong>Tanggal:</strong> {{ $order->created_at }}</p>
        <p><strong>Alamat Pengiriman:</strong> {{ $order->shipping_address }}</p>
        <p><strong>Metode Pembayaran:</strong> {{ $order->payment_method }}</p>
        <p><strong>Total Pembayaran:</strong> Rp{{ number_format($order->total_amount, 0, ',', '.') }}</p>
    </div>

    <p>Pesanan Anda saat ini sedang diproses oleh penjual. Anda dapat memantau status pengiriman pada menu <strong>Pesanan Saya</strong> di aplikasi kami.</p>

    <p>Salam hangat,<br><strong>Brodev - Team Support</strong></p>
    <hr style="border: none; border-top: 1px solid #eee; margin-top: 40px;" />
    <p style="font-size: 0.8rem; color: #777;">&copy; 2026 Brodev - Online Store. All rights reserved.</p>
</body>
</html>
