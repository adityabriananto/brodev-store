# Walkthrough: Brodev - Online Store

Aplikasi toko online **Brodev - Online Store** telah berhasil dibuat dan dioptimalkan secara lengkap menggunakan stack **Laravel 11**, **Vue.js 3** (melalui **Inertia.js**), **PostgreSQL**, **Docker**, dan **Capistrano**.

Semua target fitur, persyaratan arsitektur, optimasi kecepatan halaman di bawah **300ms**, transisi halaman yang smooth, dan riwayat status paket telah berhasil diimplementasikan dengan performa luar biasa.

---

## Ringkasan Perubahan & File yang Dibuat

Berikut adalah daftar file utama yang telah dibuat dan dikonfigurasi dalam proyek ini:

### 1. File Konfigurasi Docker & Capistrano
*   `Dockerfile` - Container PHP 8.3 FPM + ekstensi PostgreSQL, MySQL, zip, GD, dll.
*   `docker-compose.yml` - Konfigurasi Nginx, PHP, PostgreSQL 15, Node.js 20 (Vite Hot Reload), dan **Queue Worker** container.
*   `docker-compose/nginx/default.conf` - Nginx proxy reverse ke PHP-FPM container.
*   `.dockerignore` - Mengabaikan cache & dependency.
*   `Capfile` - Entrypoint konfigurasi deployment Capistrano.
*   `config/deploy.rb` - Resep deploy otomatis zero-downtime (composer, npm, migrate, symlink switch, reload php).
*   `config/deploy/production.rb` & `staging.rb` - Konfigurasi target server dan SSH.

### 2. Skema & Model Database (Laravel)
*   `database/migrations/..._create_users_table.php` - Ditambahkan kolom `role` ('buyer', 'seller', 'superadmin').
*   `database/migrations/..._create_products_table.php` - Tabel produk (nama, deskripsi, harga, stok, image_path, seller_id).
*   `database/migrations/..._create_cart_items_table.php` - Tabel keranjang belanja.
*   `database/migrations/..._create_orders_table.php` - Tabel pesanan (total, alamat, status paket, metode bayar).
*   `database/migrations/..._create_order_items_table.php` - Item pesanan detail.
*   `database/migrations/..._create_order_status_histories_table.php` - **[NEW]** Tabel riwayat log status pengiriman (status, changed_by, timestamps).
*   `database/seeders/DatabaseSeeder.php` - Seeder default untuk akun demo & produk dummy.
*   `app/Models/` (`User.php`, `Product.php`, `CartItem.php`, `Order.php`, `OrderItem.php`, `OrderStatusHistory.php`) - Definisi relasi database Eloquent lengkap.

### 3. Logika Controller, Middleware, & Optimization (Backend)
*   `app/Http/Middleware/EnsureUserIsBuyer.php` - Proteksi rute buyer.
*   `app/Http/Middleware/EnsureUserIsSeller.php` - Proteksi rute seller.
*   `app/Http/Middleware/HandleInertiaRequests.php` - Sharing data user login & **cached cart count** ke frontend Vue.
*   `app/Http/Controllers/AuthController.php` - Logika registrasi & login multi-role.
*   `app/Http/Controllers/ProductController.php` - Logika katalog buyer (**menggunakan cache**) dan CRUD produk seller dengan pembersihan cache otomatis.
*   `app/Http/Controllers/CartController.php` - Logika keranjang belanja dengan penghapusan cache cart count.
*   `app/Http/Controllers/OrderController.php` - Logika checkout transaksi aman dengan Database Transaction, pembersihan cache, pencatatan status awal, dan **asynchronous queue job dispatch**.
*   `app/Http/Controllers/SellerOrderController.php` - Logika ringkasan statistik, update status paket, dan pencatatan riwayat perubahan status oleh seller.
*   `app/Jobs/SendOrderConfirmation.php` - Queue Job untuk mengirim email konfirmasi pesanan di latar belakang.
*   `app/Mail/OrderConfirmed.php` - Mailable template untuk email konfirmasi pembelian.
*   `routes/web.php` - Pemetaan rute dan proteksi middleware.

### 4. Tampilan Frontend (Vue.js & CSS)
*   `resources/views/app.blade.php` - Blade entrypoint Inertia.
*   `resources/js/app.js` - Bootstrapping Vue 3 & Inertia dengan **Inertia Progress Bar**.
*   `resources/css/app.css` - Desain Vanilla CSS premium dengan Outfit font, Light/Dark Mode variables, glassmorphism, responsive grid, **smooth page transition animations**, dan optimasi transition overhead.
*   `resources/js/Layouts/AppLayout.vue` - Master layout (Navbar, Search, Superadmin toggle mode, Theme toggle, copyright 2026).
*   `resources/js/Pages/`
    *   `Auth/Login.vue` & `Auth/Register.vue` - Autentikasi.
    *   `Buyer/Home.vue` - Katalog produk.
    *   `Buyer/ProductDetail.vue` - Detail produk & input quantity.
    *   `Buyer/Cart.vue` - Keranjang belanja.
    *   `Buyer/Checkout.vue` - Formulir checkout aman.
    *   `Buyer/Orders.vue` - Riwayat belanja buyer dilengkapi **Timeline Riwayat Perjalanan Paket**.
    *   `Seller/Dashboard.vue` - Analitik penjualan seller.
    *   `Seller/Products.vue` - CRUD tabel produk & modal form + file upload preview.
    *   `Seller/Orders.vue` - Tabel pesanan masuk dilengkapi **Mini-Timeline Riwayat Status**.

---

## Detail Optimasi Transisi Halaman (Smooth Navigation)

Untuk mengatasi keluhan perpindahan halaman yang terasa berat dan tidak smooth, kami melakukan optimasi performa frontend berikut:

1.  **Menghapus Universal CSS Transition Anti-Pattern**:
    *   Sebelumnya, terdapat aturan transition universal di file `app.css` pada selektor `*` (`transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;`).
    *   Aturan ini memaksa browser menghitung ulang transisi warna pada *setiap* elemen DOM saat halaman berubah, membebani CPU. Kami telah menghapusnya dan hanya menerapkan transisi pada kelas selektif seperti `.btn`, `.card`, dan `.nav-item`.
2.  **Menambahkan Page Transition Slide-Up**:
    *   Kami menerapkan animasi `@keyframes pageTransition` dengan akselerasi hardware (`will-change: transform, opacity`) pada kontainer utama `<main>`. 
    *   Setiap kali halaman memuat, konten akan melakukan transisi fade-in dan slide-up secara halus (durasi 0.4 detik).
3.  **Mengaktifkan Inertia Progress Bar**:
    *   Kami mengintegrasikan konfigurasi progress bar di `app.js` menggunakan warna aksen hijau emerald (`#10b981`). Saat navigasi memuat data AJAX dari server, indikator visual di bagian atas layar akan menyala memberikan umpan balik instan.

---

## Fitur Riwayat Perubahan Status Paket (Timeline)

Kami membuat sistem logging untuk merekam setiap aktivitas perubahan status paket pesanan:

1.  **Pencatatan Status Awal**: Saat pembeli melakukan checkout sukses, sistem secara otomatis mencatat riwayat pertama (`pending` / *Menunggu Konfirmasi*) yang dilakukan oleh pembeli tersebut.
2.  **Pencatatan Perubahan Status**: Setiap kali penjual (atau superadmin) mengubah status paket dari dashboard (misal dari *Diproses* menjadi *Dikirim*), sistem merekam waktu kejadian, status baru, dan nama akun yang melakukan perubahan tersebut.
3.  **Tampilan Timeline Pembeli**: Halaman **Pesanan Saya** milik pembeli menampilkan diagram alur riwayat log waktu perjalanan paket secara vertikal.
4.  **Tampilan Timeline Penjual**: Halaman **Pesanan Masuk** milik penjual menampilkan log mini-timeline status di bawah informasi pembeli pada tabel untuk audit internal cepat.

---

## Pratinjau Produk Dummy Katalog (Hasil Generator Gambar)

Berikut adalah visualisasi dari 5 produk premium berorientasi developer yang kami generate gambarnya untuk mengisi katalog toko online Brodev Store:

````carousel
![Brodev Mechanical Keyboard](C:\Users\adity\.gemini\antigravity\brain\79bd1d28-5f5b-4591-baed-e461771b0895\keyboard_1782606374305.png)
<!-- slide -->
![Brodev Ergonomic Mouse](C:\Users\adity\.gemini\antigravity\brain\79bd1d28-5f5b-4591-baed-e461771b0895\mouse_1782606387824.png)
<!-- slide -->
![Brodev Minimalist Deskmat](C:\Users\adity\.gemini\antigravity\brain\79bd1d28-5f5b-4591-baed-e461771b0895\deskmat_1782606399806.png)
<!-- slide -->
![Brodev Monitor Lightbar](C:\Users\adity\.gemini\antigravity\brain\79bd1d28-5f5b-4591-baed-e461771b0895\lightbar_1782606412587.png)
<!-- slide -->
![Brodev Aluminum Laptop Stand](C:\Users\adity\.gemini\antigravity\brain\79bd1d28-5f5b-4591-baed-e461771b0895\stand_1782606424326.png)
````

---

## Laporan Stress Test (Beban Tinggi)

```text
=== HASIL STRESS TEST SPA NAVIGASI (1,000 INERTIA DISPATCH REQUESTS) ===
Menguji pemuatan katalog halaman utama dengan caching aktif (X-Inertia: true)...

=== HASIL STRESS TEST ===
Total Requests:        1,000
Total Waktu Pengujian: 2.608 detik
Throughput (RPS):      383.4 req/detik

=== METRIK LATENCY (WAKTU RESPON) ===
Minimum Latency:       2.09 ms
Rata-rata Latency:     2.39 ms (Luar biasa cepat!)
Median (p50):          2.26 ms
90th Percentile (p90): 2.69 ms
99th Percentile (p99): 3.33 ms
Maximum Latency:       35.83 ms

✅ KESIMPULAN: Target tercapai! Bahkan pada persentil 99 (p99), response time berada di 3.33 ms, jauh di bawah batas 300 ms.
```

---

## Verifikasi & Keandalan Kode

*   [x] **24 Rute Terdaftar Sempurna**: Diverifikasi sukses via `php artisan route:list`.
*   [x] **Penanganan Transaksi Checkout**: Menggunakan database transaction (`DB::beginTransaction()`) untuk memastikan tidak terjadi *race condition* saat stok berkurang secara bersamaan.
*   [x] **Superadmin Toggle View**: Beralih mode navigasi antara Buyer & Seller dalam sekali login secara dinamis dengan menyimpan status di LocalStorage.
*   [x] **Light/Dark Mode**: Persisten setelah refresh halaman menggunakan penanda atribut data-theme di root dokumen.
*   [x] **Copyright**: Tercetak di footer layout utama: `© 2026 Brodev - Online Store. All rights reserved.`
