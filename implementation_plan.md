# Rencana Implementasi: Brodev - Online Store

Aplikasi toko online mandiri (self-contained) bernama **Brodev - Online Store** dibangun dengan menggunakan **Laravel (Backend)** + **Vue.js (Frontend)** melalui **Inertia.js**, serta berjalan di dalam lingkungan **Docker**.

---

## Analisis & Rekomendasi Database: PostgreSQL vs MySQL

Sebelum memulai, berikut adalah perbandingan antara PostgreSQL dan MySQL untuk kebutuhan aplikasi toko online:

| Fitur / Parameter | PostgreSQL (Direkomendasikan) | MySQL |
| :--- | :--- | :--- |
| **Integritas Data (ACID)** | Sangat ketat. Memiliki penanganan transaksi konkuren yang unggul, sangat krusial untuk mencegah *race condition* saat checkout stok produk. | Sangat baik (menggunakan engine InnoDB), namun secara historis PostgreSQL lebih ketat dalam validasi data bawaan. |
| **Atribut Produk Dinamis** | Mendukung tipe data `JSONB` yang terindeks. Sangat ideal untuk spesifikasi produk e-commerce yang bervariasi (misal: warna untuk baju, RAM untuk HP) tanpa skema tabel yang rumit. | Mendukung tipe data `JSON`, namun indeksasi dan efisiensi query-nya masih di bawah `JSONB` PostgreSQL. |
| **Fitur Pencarian Produk** | Memiliki pencarian teks lengkap (*Full-Text Search*) bawaan yang sangat canggih dan cepat. | Memiliki fitur pencarian teks dasar, namun kurang optimal untuk pencarian produk yang kompleks. |
| **Skalabilitas & Konkurensi** | Sangat tangguh dalam menangani penulisan/pembacaan konkuren tingkat tinggi (misal: saat flash sale). | Sangat cepat untuk operasi baca (*read-heavy*), namun bisa lambat pada penulisan konkuren yang masif. |

### Rekomendasi Akhir
Kami merekomendasikan **PostgreSQL** untuk menjaga integritas data transaksi belanja, mengelola detail produk dinamis melalui kolom JSONB, dan keandalan jangka panjang. Namun, sistem akan dikonfigurasi agar mudah dipindahkan ke **MySQL** hanya dengan mengganti beberapa variabel di file `.env`.

---

## Desain Arsitektur & Alur Pengguna

### 1. Peran Pengguna (User Roles)
Sistem memiliki 3 tingkat akses berdasarkan kolom `role` pada tabel `users`:
*   **Buyer (Pembeli)**:
    *   Registrasi dan Login.
    *   Menjelajahi produk (pencarian, filter kategori).
    *   Menambahkan produk ke keranjang belanja (*add to cart*).
    *   Melakukan checkout (mengisi alamat & detail pesanan).
    *   Melihat riwayat belanja dan status pengiriman pesanan.
*   **Seller (Penjual)**:
    *   Registrasi dan Login.
    *   Dashboard Seller: Ringkasan penjualan dan produk.
    *   Manajemen Produk: Menambah, mengubah, dan menghapus produk miliknya sendiri.
    *   Manajemen Pesanan: Melihat pesanan yang masuk dan memperbarui status paket (`pending`, `processed`, `shipped`, `delivered`).
*   **Superadmin**:
    *   Melakukan fungsi **Buyer** dan **Seller** sekaligus dalam satu akun (Single Login).
    *   Terdapat navigasi khusus untuk berpindah tampilan/dashboard antara Buyer Mode dan Seller Mode.

### 2. Desain Antarmuka (UI)
*   **Tema**: Mendukung **Light Mode** dan **Dark Mode** secara dinamis (menggunakan CSS Variables yang disimpan di LocalStorage).
*   **Estetika**: Desain modern, bersih, minimalis dengan tipografi premium (Google Fonts: Outfit / Inter) dan gradien warna yang elegan (misal: ungu-indigo untuk Dark Mode dan putih-keabuan yang bersih untuk Light Mode).
*   **Copyright**: Semua halaman menampilkan footer dinamis dengan hak cipta tahun ini (2026): `© 2026 Brodev - Online Store. All rights reserved.`

### 3. Deployment dengan Capistrano (Zero-Downtime)
Capistrano akan digunakan untuk mengotomatiskan proses *zero-downtime deployment*. Skema kerjanya adalah sebagai berikut:
*   **Struktur Direktori Target**: Di server tujuan, Capistrano mengelola struktur direktori berikut:
    *   `/var/www/brodev-store/releases/` (Berisi folder rilis berkode waktu, misal: `/releases/20260628000000/`)
    *   `/var/www/brodev-store/shared/` (Berisi file/folder persisten seperti file `.env` dan folder `storage/` yang digunakan bersama oleh semua rilis)
    *   `/var/www/brodev-store/current` (Sebuah symlink dinamis yang selalu menunjuk ke rilis aktif terbaru di dalam folder `/releases/`)
*   **Prosedur Zero-Downtime**:
    *   Capistrano mengkloning kode versi terbaru dari repositori Git ke folder rilis baru di bawah `/releases/`.
    *   Melakukan instalasi dependency (`composer install --no-dev`) dan membangun aset frontend (`npm install && npm run build`) dalam folder rilis baru tersebut secara terisolasi tanpa mengganggu aplikasi yang sedang berjalan.
    *   Membuat symlink dari `/shared/.env` dan `/shared/storage` ke rilis baru.
    *   Menjalankan migrasi database (`php artisan migrate --force`).
    *   Secara atomik mengalihkan symlink `/var/www/brodev-store/current` dari rilis lama ke rilis baru.
    *   Memuat ulang *OPcache* PHP (reload PHP-FPM) atau memicu restart container Nginx/PHP secara *graceful* (menggunakan sinyal reload) sehingga tidak ada request pengguna yang terputus selama proses rilis.

---

## Struktur Database (Skema)

1.  **users**: `id`, `name`, `email`, `password`, `role` ('buyer', 'seller', 'superadmin'), `created_at`, `updated_at`.
2.  **products**: `id`, `seller_id` (foreign key ke users), `name`, `description`, `price`, `stock`, `image_path`, `created_at`, `updated_at`.
3.  **cart_items**: `id`, `user_id` (foreign key ke users), `product_id` (foreign key ke products), `quantity`, `created_at`, `updated_at`.
4.  **orders**: `id`, `user_id` (foreign key ke users), `total_amount`, `status` ('pending', 'processed', 'shipped', 'delivered'), `shipping_address`, `payment_method`, `created_at`, `updated_at`.
5.  **order_items**: `id`, `order_id` (foreign key ke orders), `product_id` (foreign key ke products), `price`, `quantity`, `created_at`, `updated_at`.

---

## Konfigurasi Docker (Mandiri)

Aplikasi akan dibungkus menggunakan Docker agar bisa langsung dijalankan dengan perintah `docker-compose up -d`. Layanan yang didefinisikan:
1.  **app**: Service PHP 8.3 FPM + Nginx (menggunakan Dockerfile multi-stage untuk kemudahan development & production).
2.  **db**: PostgreSQL 15 (atau MySQL jika dipilih) sebagai database utama.
3.  **node**: Menjalankan Node.js untuk kompilasi dan hot-reload asset Vue 3 via Vite (opsional untuk local dev, aset juga dapat dikompilasi ke folder public agar app berjalan mandiri tanpa Node container aktif).

---

## Rencana Langkah Kerja

### Fase 1: Inisialisasi Proyek, Docker, & Capistrano Setup
1.  Membuat direktori proyek `brodev-store` di `g:\Development\brodev-store`.
2.  Menginstal Laravel menggunakan Composer lokal.
3.  Mengonfigurasi Vue 3 + Inertia.js di dalam proyek Laravel.
4.  Membuat konfigurasi Docker (`Dockerfile`, `docker-compose.yml`, `.dockerignore`, dan konfigurasi Nginx).
5.  Menginisialisasi konfigurasi Capistrano (`Capfile`, `config/deploy.rb`, `config/deploy/production.rb`, `config/deploy/staging.rb`).

### Fase 2: Pengembangan Backend (Laravel)
1.  Membuat migration database dan mendefinisikan relasi model (`User`, `Product`, `CartItem`, `Order`, `OrderItem`).
2.  Membuat seeder untuk produk dummy dan akun default (Buyer, Seller, Superadmin).
3.  Membangun API/Controller:
    *   `AuthController` untuk registrasi & login.
    *   `ProductController` untuk CRUD produk (Seller/Superadmin) & pencarian produk (Buyer).
    *   `CartController` untuk tambah/update/hapus keranjang belanja (Buyer/Superadmin).
    *   `OrderController` untuk pembuatan pesanan (Checkout) dan pelacakan pesanan.
    *   `SellerOrderController` untuk memperbarui status paket (Seller/Superadmin).

### Fase 3: Pengembangan Frontend (Vue.js + Inertia)
1.  Membuat sistem CSS Kustom (`resources/css/app.css`) dengan variabel warna untuk Light/Dark mode.
2.  Membuat layout utama (`AppLayout.vue`) lengkap dengan:
    *   Header Navigasi (Logo Brodev, Search bar, Link Dashboard, Cart Icon dengan badge, Profil & Logout).
    *   Toggle Light/Dark Mode.
    *   Footer dengan Copyright Tahun 2026.
3.  Membuat halaman-halaman Vue:
    *   `Auth/Login.vue` dan `Auth/Register.vue`.
    *   `Buyer/Home.vue` (Katalog produk, pencarian).
    *   `Buyer/ProductDetail.vue` (Detail produk, tambah ke keranjang).
    *   `Buyer/Cart.vue` (Keranjang belanja).
    *   `Buyer/Checkout.vue` (Formulir checkout).
    *   `Buyer/Orders.vue` (Daftar pesanan pembeli).
    *   `Seller/Dashboard.vue` (Ringkasan statistik).
    *   `Seller/Products.vue` (Tabel produk & form tambah/edit modal).
    *   `Seller/Orders.vue` (Daftar pesanan masuk & dropdown status paket).

### Fase 4: Integrasi & Verifikasi
1.  Menyambungkan frontend Vue dengan backend Laravel via routing Inertia.
2.  Verifikasi fitur Light/Dark mode.
3.  Pengujian skenario Buyer (Belanja sampai Checkout).
4.  Pengujian skenario Seller (Tambah produk & update status paket).
5.  Pengujian skenario Superadmin (Beralih fungsi Buyer & Seller sekali login).
### Fase 5: Uji Coba Deployment (Capistrano)
1.  Melakukan simulasi atau penyiapan environment Capistrano.
2.  Membuat script deployment yang mengotomatiskan Composer install, NPM build, migrasi database, dan pembaruan symlink `current` secara atomik.
3.  Memasukkan instruksi deployment lengkap di dalam `README.md`.

---

## Rencana Verifikasi

### Pengujian Manual
*   **Registrasi & Login**: Registrasi buyer, seller, dan verifikasi session login.
*   **Alur Belanja Buyer**: Masuk halaman produk -> add to cart -> lihat keranjang -> edit jumlah -> checkout -> pesanan sukses.
*   **Alur Seller**: Login seller -> tambah produk baru -> lihat di katalog buyer -> terima pesanan -> update status paket -> verifikasi perubahan status di sisi buyer.
*   **Fungsi Superadmin**: Login superadmin -> verifikasi akses menu seller (tambah produk) dan pembeli (berbelanja & checkout).
*   **UI Tema**: Klik tombol toggle tema -> pastikan warna berubah mulus (animasi transisi) dan persisten setelah refresh halaman.
