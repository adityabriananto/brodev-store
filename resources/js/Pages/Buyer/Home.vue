<template>
  <AppLayout>
    <div class="hero-section">
      <h1 class="hero-title">Brodev - Online Store</h1>
      <p class="hero-subtitle">Perlengkapan Premium untuk Developer & Profesional IT</p>
    </div>

    <!-- Product Grid -->
    <div v-if="products.length > 0" class="grid grid-cols-4" style="margin-top: 2rem;">
      <div v-for="product in products" :key="product.id" class="card" @click="viewDetail(product.id)">
        <div class="card-img-wrapper">
          <img :src="product.image_path || '/images/placeholder.png'" :alt="product.name" class="card-img" />
          <span class="badge badge-primary seller-badge">{{ product.seller.name }}</span>
        </div>
        <div class="card-body">
          <h3 class="card-title">{{ product.name }}</h3>
          <p class="card-desc">{{ product.description }}</p>
          <div class="card-footer">
            <span class="price-tag">{{ formatRupiah(product.price) }}</span>
            <div class="stock-info">
              <span v-if="product.stock > 0" class="badge badge-success">Stok: {{ product.stock }}</span>
              <span v-else class="badge badge-danger">Habis</span>
            </div>
          </div>
          <button 
            v-if="canBuy(product.stock)" 
            class="btn btn-primary btn-sm" 
            style="margin-top: 1rem; width: 100%;" 
            @click.stop="addToCart(product.id)"
          >
            + Keranjang
          </button>
        </div>
      </div>
    </div>
    
    <div v-else class="no-products">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted); margin-bottom: 1rem;"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
      <p>Tidak ada produk ditemukan.</p>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
  products: Array,
  filters: Object,
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

const viewDetail = (id) => {
  router.visit(`/products/${id}`);
};

const addToCart = (productId) => {
  if (!user.value) {
    router.visit('/login');
    return;
  }
  router.post('/cart', {
    product_id: productId,
    quantity: 1
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
.hero-section {
  text-align: center;
  padding: 3rem 1.5rem;
  background: linear-gradient(135deg, var(--bg-secondary), var(--bg-tertiary));
  border: 1px solid var(--border-color);
  border-radius: 1rem;
  margin-bottom: 2rem;
  position: relative;
  overflow: hidden;
}
.hero-section::after {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, var(--card-glow) 0%, transparent 60%);
  pointer-events: none;
}
.hero-title {
  font-size: 2.25rem;
  font-weight: 800;
  margin-bottom: 0.75rem;
  letter-spacing: -0.02em;
}
.hero-subtitle {
  font-size: 1.1rem;
  color: var(--text-secondary);
}
.seller-badge {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  z-index: 10;
}
.no-products {
  text-align: center;
  padding: 4rem 2rem;
  background-color: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 1rem;
  color: var(--text-secondary);
}
.card {
  cursor: pointer;
}
</style>
