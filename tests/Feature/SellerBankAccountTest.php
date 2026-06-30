<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerBankAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_update_bank_account(): void
    {
        $response = $this->post(route('seller.bank-account.update'), [
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'John Doe',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_buyer_cannot_update_bank_account(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $response = $this->actingAs($buyer)->post(route('seller.bank-account.update'), [
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'John Doe',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'Akses ditolak. Halaman ini hanya untuk Seller.');
    }

    public function test_seller_can_update_bank_account_with_valid_details(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        $response = $this->actingAs($seller)->post(route('seller.bank-account.update'), [
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'John Doe',
        ]);

        $response->assertStatus(302); // Redirect back
        $response->assertSessionHas('success');

        $seller->refresh();
        $this->assertEquals('Bank Mandiri', $seller->bank_name);
        $this->assertEquals('1234567890', $seller->bank_account_number);
        $this->assertEquals('John Doe', $seller->bank_account_holder);
    }

    public function test_seller_bank_details_validation_rules(): void
    {
        $seller = User::factory()->create(['role' => 'seller']);

        // Missing bank name
        $response = $this->actingAs($seller)->post(route('seller.bank-account.update'), [
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'John Doe',
        ]);
        $response->assertSessionHasErrors(['bank_name']);

        // Missing bank account number
        $response = $this->actingAs($seller)->post(route('seller.bank-account.update'), [
            'bank_name' => 'Bank Mandiri',
            'bank_account_holder' => 'John Doe',
        ]);
        $response->assertSessionHasErrors(['bank_account_number']);

        // Missing bank account holder
        $response = $this->actingAs($seller)->post(route('seller.bank-account.update'), [
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1234567890',
        ]);
        $response->assertSessionHasErrors(['bank_account_holder']);
    }

    public function test_seller_bank_details_are_passed_to_dashboard_and_orders(): void
    {
        $seller = User::factory()->create([
            'role' => 'seller',
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'John Doe',
        ]);

        // Dashboard
        $response = $this->actingAs($seller)->get(route('seller.dashboard'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('seller')
            ->where('seller.bank_name', 'Bank Mandiri')
            ->where('seller.bank_account_number', '1234567890')
            ->where('seller.bank_account_holder', 'John Doe')
        );

        // Orders index
        $response = $this->actingAs($seller)->get(route('seller.orders'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('seller')
            ->where('seller.bank_name', 'Bank Mandiri')
            ->where('seller.bank_account_number', '1234567890')
            ->where('seller.bank_account_holder', 'John Doe')
        );
    }

    public function test_buyer_checkout_receives_seller_bank_details(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create([
            'role' => 'seller',
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'John Doe',
        ]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'name' => 'Produk Keren',
            'description' => 'Deskripsi Produk Keren',
            'price' => 100000,
            'stock' => 10,
        ]);

        CartItem::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($buyer)->get(route('checkout.show'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('cartItems.0.product.seller')
            ->where('cartItems.0.product.seller.bank_name', 'Bank Mandiri')
            ->where('cartItems.0.product.seller.bank_account_number', '1234567890')
            ->where('cartItems.0.product.seller.bank_account_holder', 'John Doe')
        );
    }

    public function test_buyer_orders_receive_seller_bank_details(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $seller = User::factory()->create([
            'role' => 'seller',
            'bank_name' => 'Bank Mandiri',
            'bank_account_number' => '1234567890',
            'bank_account_holder' => 'John Doe',
        ]);

        $product = Product::create([
            'seller_id' => $seller->id,
            'name' => 'Produk Keren',
            'description' => 'Deskripsi Produk Keren',
            'price' => 100000,
            'stock' => 10,
        ]);

        $order = Order::create([
            'user_id' => $buyer->id,
            'total_amount' => 200000,
            'status' => 'unpaid',
            'shipping_address' => 'Jl. Merdeka No. 45',
            'payment_method' => 'Transfer Bank',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'price' => 100000,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($buyer)->get(route('orders.index'));
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('orders.0.items.0.product.seller')
            ->where('orders.0.items.0.product.seller.bank_name', 'Bank Mandiri')
            ->where('orders.0.items.0.product.seller.bank_account_number', '1234567890')
            ->where('orders.0.items.0.product.seller.bank_account_holder', 'John Doe')
        );
    }
}
