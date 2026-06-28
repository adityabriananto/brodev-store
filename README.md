# Brodev - Online Store

Aplikasi E-Commerce modern yang dibangun menggunakan **Laravel (Backend)** + **Vue.js 3 (Frontend)** melalui **Inertia.js** dan diatur menggunakan **Docker** untuk kemudahan deployment mandiri.

Aplikasi ini dirancang dengan antarmuka yang bersih, minimalis, dan mendukung **Light/Dark Mode** serta dilengkapi dengan hak cipta dinamis berbasis tahun ini (**2026**).

---

## Fitur Utama

1.  **Sistem Multi-User & Multi-Role**:
    *   **Buyer (Pembeli)**: Registrasi, login, jelajah katalog produk, cari produk, kelola keranjang belanja, checkout transaksi dengan penanganan stok aman, dan riwayat pesanan (COD & Transfer).
    *   **Seller (Penjual)**: Dashboard statistik penjualan, CRUD produk mandiri dengan upload foto produk, dan memperbarui status paket pesanan masuk (`pending`, `processed`, `shipped`, `delivered`).
    *   **Superadmin**: Akun khusus yang dapat bertindak sebagai Buyer maupun Seller sekaligus hanya dengan sekali login. Disediakan tombol toggle mode (Buyer Mode / Seller Mode) pada navigasi atas.
2.  **Theme System (Light/Dark Mode)**: Transisi mulus antara mode terang dan gelap yang persisten menggunakan LocalStorage.
3.  **Dockerized Setup**: Semua service (PHP-FPM, Nginx, PostgreSQL, Node/Vite) terkonfigurasi di Docker Compose untuk dijalankan secara instan.
4.  **Zero-Downtime Deployment**: Dilengkapi dengan konfigurasi **Capistrano** untuk proses deployment otomatis tanpa gangguan layanan.

---

## Spesifikasi Stack

*   **Backend**: Laravel 11 (PHP 8.3)
*   **Frontend**: Vue.js 3 (Inertia.js + Vite)
*   **Database**: PostgreSQL 15 (Direkomendasikan demi integritas transaksi & optimalisasi data atribut JSONB) atau MySQL
*   **Aset & Styling**: Custom Vanilla CSS (Outfit Google Font, Responsive Layout, Glassmorphism, Micro-animations)
*   **Deployment**: Capistrano 3

---

## Cara Menjalankan Aplikasi Menggunakan Docker

Pastikan Anda telah menginstal **Docker** dan **Docker Compose** di komputer Anda.

### 1. Persiapan Environment
Salin file konfigurasi `.env`:
```bash
cp .env.example .env
```
Secara default, file `.env` telah disesuaikan untuk konfigurasi database di Docker:
```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=brodev_store
DB_USERNAME=postgres
DB_PASSWORD=secret
```

### 2. Jalankan Container Docker
Jalankan perintah berikut untuk mengunduh image, membangun container, dan menjalankannya di latar belakang:
```bash
docker-compose up -d --build
```
Perintah ini akan menyalakan empat service:
*   `brodev-app`: Container PHP 8.3 FPM.
*   `brodev-web`: Container Nginx sebagai web server (Port: `http://localhost:8000`).
*   `brodev-db`: Database PostgreSQL 15 (Port: `5432`).
*   `brodev-node`: Container Node.js 20 untuk menjalankan compiler Vite dev server (Port: `5173`).

### 3. Jalankan Dependency & Migrasi di Dalam Container
Jalankan perintah-perintah berikut untuk menginstal composer dependency, migrasi database, dan seed data awal:

```bash
# Masuk ke container app
docker-compose exec app composer install

# Generate application key
docker-compose exec app php artisan key:generate

# Jalankan migrasi database beserta data seed awal
docker-compose exec app php artisan migrate:fresh --seed
```

### 4. Buka Aplikasi di Browser
Akses aplikasi melalui alamat:
*   **Aplikasi Toko Online**: `http://localhost:8000`
*   **Vite Hot Reload Server**: `http://localhost:5173`

---

## Akun Pengujian Default (Seeded)

Database telah dilengkapi dengan akun pengujian berikut untuk mempermudah demonstrasi:

1.  **Superadmin**:
    *   **Email**: `admin@brodev.com`
    *   **Password**: `password`
    *   *Fitur*: Dapat berganti peran sebagai Buyer/Seller lewat navigasi atas secara instan.
2.  **Seller (Penjual)**:
    *   **Email**: `seller@brodev.com`
    *   **Password**: `password`
    *   *Fitur*: Mengelola produk dan memproses pengiriman pesanan.
3.  **Buyer (Pembeli)**:
    *   **Email**: `buyer@brodev.com`
    *   **Password**: `password`
    *   *Fitur*: Menambahkan barang ke keranjang dan checkout.

---

## Deployment Menggunakan Capistrano (Zero-Downtime)

Aplikasi dilengkapi dengan konfigurasi Capistrano untuk deployment otomatis.

### 1. Struktur Folder di Server Target
Capistrano akan mengatur struktur folder di server tujuan sebagai berikut:
*   `/var/www/brodev-store/releases/` (Folder rilis rilis lama & baru)
*   `/var/www/brodev-store/shared/` (Berisi `.env` dan folder `storage` yang persisten)
*   `/var/www/brodev-store/current` (Symlink dinamis yang menunjuk ke rilis terbaru)

### 2. Cara Menjalankan Deploy
1.  Sesuaikan alamat server target Anda di file `config/deploy/production.rb` atau `config/deploy/staging.rb`.
2.  Pasang SSH Key publik Anda di server target.
3.  Jalankan perintah deploy dari mesin lokal Anda:
    ```bash
    # Install Capistrano gems locally (requires Ruby)
    bundle install

    # Jalankan simulasi deploy (staging / production)
    bundle exec cap production deploy:check

    # Jalankan deploy penuh (mengklon git, install composer, build assets, migrate, switch symlink)
    bundle exec cap production deploy
    ```

Proses peralihan symlink `current` berlangsung secara instan (atomic), dan PHP-FPM akan dimuat ulang secara otomatis untuk membersihkan OPcache, menjamin deployment tanpa downtime.

---

## Hak Cipta
Hak cipta halaman toko online ini dilindungi berdasarkan tahun 2026:
`© 2026 Brodev - Online Store. All rights reserved.`
