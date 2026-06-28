<template>
  <AppLayout>
    <div style="margin-bottom: 1.5rem;">
      <Link href="/" class="btn btn-secondary btn-sm">
        &larr; Kembali ke Katalog
      </Link>
    </div>

    <div class="product-detail-card">
      <div class="product-gallery">
        <img :src="product.image_path || '/images/placeholder.png'" :alt="product.name" class="detail-img" />
      </div>

      <div class="product-info-panel">
        <span class="badge badge-primary seller-badge">Penjual: {{ product.seller.name }}</span>
        <h1 class="product-title">{{ product.name }}</h1>
        <div class="price-container">
          <span class="price-value">{{ formatRupiah(product.price) }}</span>
        </div>

        <div class="product-divider"></div>

        <p class="product-desc-title">Deskripsi Produk</p>
        <p class="product-desc">{{ product.description }}</p>

        <div class="product-divider"></div>

        <div class="purchase-actions" v-if="canBuy(product.stock)">
          <div class="quantity-selector">
            <span class="form-label" style="margin-bottom: 0;">Jumlah:</span>
            <div class="quantity-input-group">
              <button class="btn btn-secondary qty-btn" @click="decrementQty" :disabled="quantity <= 1">-</button>
              <input type="number" class="form-control qty-input" v-model="quantity" min="1" :max="product.stock" readonly />
              <button class="btn btn-secondary qty-btn" @click="incrementQty" :disabled="quantity >= product.stock">+</button>
            </div>
            <span class="stock-indicator">Stok tersedia: <strong>{{ product.stock }}</strong></span>
          </div>

          <button class="btn btn-primary add-cart-btn" @click="addToCart" :disabled="processing">
            {{ processing ? 'Menambahkan...' : 'Tambah ke Keranjang' }}
          </button>
        </div>

        <div v-else class="out-of-stock-alert">
          <span v-if="product.stock <= 0" class="badge badge-danger">Stok Habis</span>
          <span v-else class="badge badge-warning">Hanya Buyer yang bisa berbelanja</span>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
  product: Object
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const activeMode = computed(() => {
  if (user.value && user.value.role === 'superadmin') {
    return localStorage.getItem('superadmin_mode') || 'buyer';
  }
  return 'buyer';
});

const canBuy = (stock) => {
  if (stock <= 0) return false;
  if (!user.value) return true;
  return user.value.role === 'buyer' || (user.value.role === 'superadmin' && activeMode.value === 'buyer');
};

const quantity = ref(1);
const processing = ref(false);

const incrementQty = () => {
  if (quantity.value < props.product.stock) {
    quantity.value++;
  }
};

const decrementQty = () => {
  if (quantity.value > 1) {
    quantity.value--;
  }
};

const addToCart = () => {
  if (!user.value) {
    router.visit('/login');
    return;
  }
  processing.value = true;
  router.post('/cart', {
    product_id: props.product.id,
    quantity: quantity.value
  }, {
    onFinish: () => {
      processing.value = false;
    }
  });
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
.product-detail-card {
  display: flex;
  background-color: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  padding: 2rem;
  gap: 2.5rem;
}
.product-gallery {
  width: 45%;
  flex-shrink: 0;
  border-radius: 0.75rem;
  overflow: hidden;
  border: 1px solid var(--border-color);
  aspect-ratio: 1;
}
.detail-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.product-info-panel {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}
.seller-badge {
  width: fit-content;
  margin-bottom: 0.75rem;
}
.product-title {
  font-size: 1.875rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
  line-height: 1.2;
}
.price-container {
  margin-bottom: 1.5rem;
}
.price-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-primary);
}
.product-divider {
  height: 1px;
  background-color: var(--border-color);
  margin: 1.5rem 0;
}
.product-desc-title {
  font-weight: 700;
  font-size: 1rem;
  margin-bottom: 0.5rem;
}
.product-desc {
  font-size: 0.95rem;
  color: var(--text-secondary);
  line-height: 1.6;
}
.purchase-actions {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.quantity-selector {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}
.quantity-input-group {
  display: flex;
  align-items: center;
}
.qty-btn {
  padding: 0.375rem 0.75rem;
  font-size: 1rem;
  border-radius: 0;
}
.qty-btn:first-child {
  border-top-left-radius: 0.375rem;
  border-bottom-left-radius: 0.375rem;
}
.qty-btn:last-child {
  border-top-right-radius: 0.375rem;
  border-bottom-right-radius: 0.375rem;
}
.qty-input {
  width: 50px;
  text-align: center;
  border-radius: 0;
  padding: 0.375rem;
  height: 2.25rem;
}
.stock-indicator {
  font-size: 0.875rem;
  color: var(--text-secondary);
}
.add-cart-btn {
  width: 100%;
  max-width: 250px;
  padding: 0.75rem 1.5rem;
  font-size: 1rem;
}
.out-of-stock-alert {
  padding: 1rem;
  background-color: var(--bg-tertiary);
  border-radius: 0.5rem;
  text-align: center;
}

@media (max-width: 768px) {
  .product-detail-card {
    flex-direction: column;
    padding: 1.25rem;
    gap: 1.5rem;
  }
  .product-gallery {
    width: 100%;
  }
  .add-cart-btn {
    max-width: 100%;
  }
}
</style>
