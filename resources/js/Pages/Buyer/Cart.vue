<template>
  <AppLayout>
    <h1 style="font-size: 1.875rem; font-weight: 800; margin-bottom: 1.5rem;">Keranjang Belanja</h1>

    <div v-if="cartItems.length > 0" class="cart-layout">
      <!-- Cart Items List -->
      <div class="cart-items-panel">
        <div v-for="item in cartItems" :key="item.id" class="cart-item-card">
          <img :src="item.product.image_path || '/images/placeholder.png'" :alt="item.product.name" class="cart-item-img" />
          <div class="cart-item-info">
            <h3 class="cart-item-title">{{ item.product.name }}</h3>
            <p class="cart-item-seller">Penjual: <strong>{{ item.product.seller.name }}</strong></p>
            <span class="price-tag">{{ formatRupiah(item.product.price) }}</span>
          </div>
          
          <div class="cart-item-qty">
            <button class="btn btn-secondary qty-btn btn-sm" @click="updateQty(item, item.quantity - 1)" :disabled="item.quantity <= 1">-</button>
            <input type="number" class="form-control qty-input" :value="item.quantity" readonly />
            <button class="btn btn-secondary qty-btn btn-sm" @click="updateQty(item, item.quantity + 1)" :disabled="item.quantity >= item.product.stock">+</button>
          </div>

          <div class="cart-item-total">
            <span class="total-label">Subtotal:</span>
            <span class="total-val">{{ formatRupiah(item.product.price * item.quantity) }}</span>
          </div>

          <button class="btn-icon delete-btn" @click="removeItem(item.id)" title="Hapus dari keranjang">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
          </button>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="summary-panel">
        <h3 class="summary-title">Ringkasan Belanja</h3>
        <div class="summary-row">
          <span>Total Item</span>
          <span>{{ totalQuantity }}</span>
        </div>
        <div class="summary-row" style="font-weight: 700; font-size: 1.1rem; border-top: 1px dashed var(--border-color); padding-top: 1rem; margin-top: 1rem;">
          <span>Total Harga</span>
          <span style="color: var(--color-primary);">{{ formatRupiah(totalPrice) }}</span>
        </div>
        <Link href="/checkout" class="btn btn-primary" style="margin-top: 1.5rem; width: 100%; padding: 0.75rem;">
          Lanjutkan ke Checkout
        </Link>
      </div>
    </div>

    <div v-else class="empty-cart">
      <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted); margin-bottom: 1.5rem;"><circle cx="8" cy="21" r="1"/><circle cx="19" cy="21" r="1"/><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"/></svg>
      <h2>Keranjang Belanja Kosong</h2>
      <p style="color: var(--text-secondary); margin-bottom: 1.5rem; margin-top: 0.5rem;">Anda belum menambahkan produk apa pun ke keranjang.</p>
      <Link href="/" class="btn btn-primary">Mulai Belanja</Link>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  cartItems: Array
});

const totalQuantity = computed(() => {
  return props.cartItems.reduce((sum, item) => sum + item.quantity, 0);
});

const totalPrice = computed(() => {
  return props.cartItems.reduce((sum, item) => sum + (item.product.price * item.quantity), 0);
});

const updateQty = (item, newQty) => {
  router.patch(`/cart/${item.id}`, {
    quantity: newQty
  }, {
    preserveScroll: true
  });
};

const removeItem = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus produk ini dari keranjang?')) {
    router.delete(`/cart/${id}`);
  }
};

const formatRupiah = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value);
};
</script>

<style scoped>
.cart-layout {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}
.cart-items-panel {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.cart-item-card {
  display: flex;
  align-items: center;
  background-color: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 1rem;
  padding: 1.25rem;
  gap: 1.25rem;
}
.cart-item-img {
  width: 80px;
  height: 80px;
  object-fit: cover;
  border-radius: 0.5rem;
  border: 1px solid var(--border-color);
  flex-shrink: 0;
}
.cart-item-info {
  flex-grow: 1;
}
.cart-item-title {
  font-size: 1.1rem;
  font-weight: 700;
}
.cart-item-seller {
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-bottom: 0.25rem;
}
.cart-item-qty {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}
.qty-btn {
  padding: 0.25rem 0.5rem;
}
.qty-input {
  width: 45px;
  text-align: center;
  border-radius: 0;
  padding: 0.25rem;
  height: 2rem;
}
.cart-item-total {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  width: 140px;
  flex-shrink: 0;
}
.total-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
}
.total-val {
  font-weight: 700;
  font-size: 1.1rem;
}
.delete-btn {
  color: var(--text-muted);
}
.delete-btn:hover {
  color: var(--color-danger);
  background-color: var(--color-danger-light);
}
.summary-panel {
  width: 320px;
  flex-shrink: 0;
  background-color: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 1rem;
  padding: 1.5rem;
  position: sticky;
  top: 6rem;
  box-shadow: var(--shadow-sm);
}
.summary-title {
  font-size: 1.2rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
  color: var(--text-secondary);
}
.empty-cart {
  text-align: center;
  padding: 5rem 2rem;
  background-color: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 1rem;
}

@media (max-width: 900px) {
  .cart-layout {
    flex-direction: column;
  }
  .summary-panel {
    width: 100%;
    position: static;
  }
}
@media (max-width: 600px) {
  .cart-item-card {
    flex-direction: column;
    align-items: flex-start;
  }
  .cart-item-total {
    align-items: flex-start;
    width: 100%;
  }
  .cart-item-qty {
    margin-top: 0.5rem;
  }
}
</style>
