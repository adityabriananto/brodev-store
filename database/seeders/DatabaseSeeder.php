<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Users
        $superadmin = User::create([
            'name' => 'Super Admin Brodev',
            'email' => 'admin@brodev.com',
            'password' => Hash::make('password'),
            'role' => 'superadmin',
        ]);

        $seller = User::create([
            'name' => 'Seller Brodev',
            'email' => 'seller@brodev.com',
            'password' => Hash::make('password'),
            'role' => 'seller',
        ]);

        $buyer = User::create([
            'name' => 'Buyer Brodev',
            'email' => 'buyer@brodev.com',
            'password' => Hash::make('password'),
            'role' => 'buyer',
        ]);

        // 2. Create Dummy Products for the Seller
        Product::create([
            'seller_id' => $seller->id,
            'name' => 'Brodev Mechanical Keyboard',
            'description' => 'Keyboard mekanik minimalis layout 75% dengan pre-lubed switches dan peredam suara busa EVA ganda. Mendukung koneksi kabel, Bluetooth, dan 2.4Ghz nirkabel. Keycaps PBT Dye-Sub berkualitas tinggi dengan pencahayaan RGB dinamis.',
            'price' => 1250000.00,
            'stock' => 15,
            'image_path' => '/images/keyboard.png',
        ]);

        Product::create([
            'seller_id' => $seller->id,
            'name' => 'Brodev Ergonomic Mouse',
            'description' => 'Mouse nirkabel dengan desain ergonomis vertikal untuk mengurangi ketegangan pergelangan tangan. Dilengkapi dengan sensor presisi tinggi hingga 16.000 DPI, tombol silent-click, dan baterai isi ulang USB-C tahan lama.',
            'price' => 450000.00,
            'stock' => 20,
            'image_path' => '/images/mouse.png',
        ]);

        Product::create([
            'seller_id' => $seller->id,
            'name' => 'Brodev Minimalist Deskmat',
            'description' => 'Deskmat berukuran 900x400x4mm dengan permukaan kain mikro-anyaman untuk kontrol mouse yang halus. Bagian dasar karet anti-selip dan jahitan tepi presisi mencegah keausan. Tahan cipratan air dan mudah dibersihkan.',
            'price' => 250000.00,
            'stock' => 30,
            'image_path' => '/images/deskmat.png',
        ]);

        Product::create([
            'seller_id' => $seller->id,
            'name' => 'Brodev Monitor Lightbar',
            'description' => 'Lampu gantung monitor dengan sudut pancaran asimetris untuk mencegah pantulan layar dan kelelahan mata. Tingkat kecerahan dan suhu warna (3000K-6500K) dapat diatur secara nirkabel dengan remote control putar dial.',
            'price' => 650000.00,
            'stock' => 10,
            'image_path' => '/images/lightbar.png',
        ]);

        Product::create([
            'seller_id' => $seller->id,
            'name' => 'Brodev Aluminum Laptop Stand',
            'description' => 'Dudukan laptop lipat dari bahan paduan aluminium premium. Ketinggian dan sudut dapat disesuaikan untuk ergonomi yang optimal. Desain berongga memastikan sirkulasi udara baik untuk mencegah laptop panas.',
            'price' => 350000.00,
            'stock' => 25,
            'image_path' => '/images/stand.png',
        ]);
    }
}
