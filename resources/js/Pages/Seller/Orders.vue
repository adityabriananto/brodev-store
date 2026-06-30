<template>
  <AppLayout>
    <div class="dashboard-wrapper">
      <!-- Sidebar -->
      <div class="sidebar">
        <p class="sidebar-title">Menu Seller</p>
        <ul class="sidebar-menu">
          <li class="sidebar-item">
            <Link :href="route('seller.dashboard')">Ringkasan</Link>
          </li>
          <li class="sidebar-item">
            <Link :href="route('seller.products')">Produk Saya</Link>
          </li>
          <li class="sidebar-item active">
            <Link :href="route('seller.orders')">Pesanan Masuk</Link>
          </li>
        </ul>
      </div>

      <!-- Main Panel -->
      <div class="main-content">
        <h1 style="font-size: 1.875rem; font-weight: 800; margin-bottom: 1.5rem;">Pesanan Masuk</h1>

        <!-- Filter Bar -->
        <div class="card filter-bar-card" style="padding: 1.25rem; margin-bottom: 1.5rem; background-color: var(--bg-secondary);">
          <form @submit.prevent="applyFilters" class="filter-form">
            <div class="filter-group">
              <label class="filter-label">No. Order</label>
              <input 
                type="text" 
                class="form-control" 
                placeholder="Cari No. Order..." 
                v-model="searchOrderNumber" 
              />
            </div>
            <div class="filter-group">
              <label class="filter-label">Tanggal Dibuat</label>
              <input 
                type="date" 
                class="form-control" 
                v-model="createdDate" 
              />
            </div>
            <div class="filter-group">
              <label class="filter-label">Tanggal Diperbarui</label>
              <input 
                type="date" 
                class="form-control" 
                v-model="updatedDate" 
              />
            </div>
            <div class="filter-actions">
              <button type="submit" class="btn btn-primary" style="height: auto; padding: 0.5rem 1rem;">Cari</button>
              <button type="button" @click="resetFilters" class="btn btn-secondary" style="height: auto; padding: 0.5rem 1rem;">Reset</button>
            </div>
          </form>
        </div>

        <div v-if="orders.length > 0" class="seller-orders-list">
          <div v-for="order in orders" :key="order.id" class="seller-order-card card">
            <!-- Order Header -->
            <div class="seller-order-header">
              <div class="order-meta">
                <span class="order-id" style="font-family: monospace;">{{ order.order_number }}</span>
                <span class="order-buyer">{{ order.user.name }}</span>
                <span class="badge" :class="getStatusClass(order.status)">{{ formatStatus(order.status) }}</span>
              </div>
              <div class="order-total-action">
                <span class="order-total">{{ formatRupiah(order.total_amount) }}</span>
                <select
                  class="form-control form-select status-select"
                  :value="order.status"
                  @change="updateOrderStatus(order.id, $event.target.value)"
                  :disabled="order.status === 'unpaid'"
                >
                  <option value="unpaid" v-if="order.status === 'unpaid'">Menunggu Pembayaran</option>
                  <option value="pending">Menunggu Konfirmasi</option>
                  <option value="processed">Diproses</option>
                  <option value="shipped">Dikirim</option>
                  <option value="delivered">Diterima</option>
                </select>
              </div>
            </div>

            <!-- Order Body -->
            <div class="seller-order-body">
              <!-- Products & Shipping -->
              <div class="order-left">
                <div class="order-section-label">Produk Dipesan</div>
                <div class="ordered-products">
                  <div v-for="item in order.items" :key="item.id" class="ordered-product-row">
                    <span class="product-name">{{ item.product.name }}</span>
                    <span class="product-qty-price">{{ item.quantity }}x &mdash; {{ formatRupiah(item.price) }}</span>
                  </div>
                </div>

                <div class="order-section-label" style="margin-top: 1rem;">Informasi Pengiriman</div>
                <div class="shipping-info">
                  <p class="shipping-address">{{ order.shipping_address }}</p>
                  <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; margin-top: 0.25rem;">
                    <span class="badge badge-secondary">{{ order.payment_method }}</span>
                    <span class="badge badge-info" v-if="order.tracking_number">Resi: <strong>{{ order.tracking_number }}</strong></span>
                  </div>
                  
                  <div style="margin-top: 0.75rem;" v-if="order.payment_proof_path">
                    <a :href="order.payment_proof_path" target="_blank" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                      Lihat Bukti Transfer
                    </a>
                  </div>
                </div>
              </div>

              <!-- Status History Timeline -->
              <div class="order-timeline" v-if="order.status_histories && order.status_histories.length">
                <div class="order-section-label">Riwayat Status</div>
                <div class="timeline">
                  <div
                    v-for="(history, index) in order.status_histories"
                    :key="history.id"
                    class="timeline-item"
                    :class="{ 'timeline-last': index === order.status_histories.length - 1 }"
                  >
                    <div class="timeline-dot" :class="getDotClass(history.status)"></div>
                    <div class="timeline-content">
                      <span class="timeline-status">{{ formatStatus(history.status) }}</span>
                      <span class="timeline-note">{{ history.note }}</span>
                      <span class="timeline-actor" v-if="history.changed_by">
                        oleh: {{ history.changed_by.name }}
                      </span>
                      <span class="timeline-time">{{ formatDate(history.created_at) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="no-orders">
          <p style="color: var(--text-secondary); text-align: center; padding: 4rem 0;">Belum ada pesanan yang masuk.</p>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';

import { ref } from 'vue';

const props = defineProps({
  orders: Array,
  filters: Object
});

const searchOrderNumber = ref(props.filters?.search_order_number || '');
const createdDate = ref(props.filters?.created_date || '');
const updatedDate = ref(props.filters?.updated_date || '');

const applyFilters = () => {
  router.get('/seller/orders', {
    search_order_number: searchOrderNumber.value,
    created_date: createdDate.value,
    updated_date: updatedDate.value,
  }, {
    preserveState: true,
    replace: true
  });
};

const resetFilters = () => {
  searchOrderNumber.value = '';
  createdDate.value = '';
  updatedDate.value = '';
  
  router.get('/seller/orders', {}, {
    preserveState: true,
    replace: true
  });
};

// Javascript route helper
const route = (name) => {
  const routes = {
    'seller.dashboard': '/seller/dashboard',
    'seller.products': '/seller/products',
    'seller.orders': '/seller/orders',
  };
  return routes[name] || '#';
};

const updateOrderStatus = (orderId, newStatus) => {
  let trackingNumber = null;
  if (newStatus === 'shipped') {
    trackingNumber = prompt('Masukkan Nomor Resi Pengiriman (Tracking Number):');
    if (trackingNumber === null) {
      router.reload({ preserveScroll: true });
      return;
    }
    if (!trackingNumber.trim()) {
      alert('Nomor Resi harus diisi untuk mengubah status menjadi Dikirim.');
      router.reload({ preserveScroll: true });
      return;
    }
  }

  router.patch(`/seller/orders/${orderId}/status`, {
    status: newStatus,
    tracking_number: trackingNumber
  }, {
    preserveScroll: true
  });
};

const getStatusClass = (status) => {
  switch (status) {
    case 'unpaid': return 'badge-danger';
    case 'pending': return 'badge-warning';
    case 'processed': return 'badge-primary';
    case 'shipped': return 'badge-info';
    case 'delivered': return 'badge-success';
    default: return 'badge-secondary';
  }
};

const getDotClass = (status) => {
  switch (status) {
    case 'unpaid': return 'dot-danger';
    case 'pending': return 'dot-warning';
    case 'processed': return 'dot-primary';
    case 'shipped': return 'dot-info';
    case 'delivered': return 'dot-success';
    default: return '';
  }
};

const formatStatus = (status) => {
  switch (status) {
    case 'unpaid': return 'Menunggu Pembayaran';
    case 'pending': return 'Menunggu Konfirmasi';
    case 'processed': return 'Diproses';
    case 'shipped': return 'Dikirim';
    case 'delivered': return 'Diterima';
    default: return status;
  }
};

const formatDate = (dateStr) => {
  const date = new Date(dateStr);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
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
.seller-orders-list {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}
.seller-order-card {
  padding: 1.25rem 1.5rem;
}
.seller-order-card:hover {
  transform: none;
}
.seller-order-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border-color);
  margin-bottom: 1.25rem;
}
.order-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.order-id {
  font-size: 1.1rem;
  font-weight: 800;
}
.order-buyer {
  font-size: 0.9rem;
  color: var(--text-secondary);
}
.order-total-action {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.order-total {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--color-primary);
  white-space: nowrap;
}
.status-select {
  font-size: 0.85rem;
  padding: 0.25rem 0.5rem;
  height: auto;
  min-width: 170px;
}
.seller-order-body {
  display: flex;
  gap: 2rem;
}
.order-left {
  flex: 1;
  min-width: 0;
}
.order-section-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--text-muted);
  margin-bottom: 0.5rem;
}
.ordered-products {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  margin-bottom: 0.5rem;
}
.ordered-product-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}
.product-name { font-weight: 500; }
.product-qty-price { color: var(--text-secondary); white-space: nowrap; margin-left: 0.5rem; }
.shipping-info { display: flex; flex-direction: column; gap: 0.35rem; }
.shipping-address { font-size: 0.85rem; color: var(--text-secondary); line-height: 1.4; }

/* Timeline */
.order-timeline {
  width: 240px;
  flex-shrink: 0;
  border-left: 1px solid var(--border-color);
  padding-left: 1.5rem;
}
.timeline {
  display: flex;
  flex-direction: column;
}
.timeline-item {
  display: flex;
  gap: 0.6rem;
  position: relative;
  padding-bottom: 0.85rem;
}
.timeline-item:not(.timeline-last)::before {
  content: '';
  position: absolute;
  left: 7px;
  top: 14px;
  bottom: 0;
  width: 2px;
  background: var(--border-color);
}
.timeline-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 1px;
  border: 2px solid transparent;
}
.dot-danger   { background: #ef4444; border-color: #fca5a5; }
.dot-warning  { background: #f59e0b; border-color: #fde68a; }
.dot-primary  { background: var(--color-primary); border-color: #c7d2fe; }
.dot-info     { background: #0ea5e9; border-color: #bae6fd; }
.dot-success  { background: #22c55e; border-color: #bbf7d0; }
.timeline-content {
  display: flex;
  flex-direction: column;
  gap: 0.05rem;
}
.timeline-status { font-size: 0.82rem; font-weight: 700; }
.timeline-note { font-size: 0.75rem; color: var(--text-secondary); line-height: 1.3; }
.timeline-actor { font-size: 0.72rem; color: var(--text-muted); font-style: italic; }
.timeline-time { font-size: 0.7rem; color: var(--text-muted); }

.no-orders {
  background-color: var(--bg-secondary);
  border: 1px dashed var(--border-color);
  border-radius: 1rem;
}
.badge-info {
  background-color: #e0f2fe;
  color: #0284c7;
}

/* Filter Bar styles */
.filter-form {
  display: flex;
  gap: 1.25rem;
  align-items: flex-end;
  flex-wrap: wrap;
}
.filter-group {
  flex: 1;
  min-width: 180px;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}
.filter-label {
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--text-muted);
  text-transform: uppercase;
}
.filter-actions {
  display: flex;
  gap: 0.5rem;
}

@media (max-width: 900px) {
  .seller-order-body { flex-direction: column; }
  .order-timeline {
    border-left: none;
    padding-left: 0;
    border-top: 1px solid var(--border-color);
    padding-top: 1rem;
    width: 100%;
  }
}

@media (max-width: 600px) {
  /* Filter form stacking */
  .filter-form {
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }
  .filter-group {
    width: 100%;
    min-width: unset;
  }
  .filter-actions {
    margin-top: 0.5rem;
    justify-content: flex-end;
  }
  .filter-actions button {
    flex: 1;
    text-align: center;
  }
  
  /* Order card header stacking */
  .seller-order-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  .order-total-action {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px dashed var(--border-color);
    padding-top: 0.75rem;
    margin-top: 0.25rem;
  }
  .status-select {
    min-width: 150px;
    flex-shrink: 0;
  }
}
</style>
