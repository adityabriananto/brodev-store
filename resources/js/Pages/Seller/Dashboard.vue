<template>
  <AppLayout>
    <div class="dashboard-wrapper">
      <!-- Sidebar -->
      <div class="sidebar">
        <p class="sidebar-title">Menu Seller</p>
        <ul class="sidebar-menu">
          <li class="sidebar-item active">
            <Link :href="route('seller.dashboard')">Ringkasan</Link>
          </li>
          <li class="sidebar-item">
            <Link :href="route('seller.products')">Produk Saya</Link>
          </li>
          <li class="sidebar-item">
            <Link :href="route('seller.orders')">Pesanan Masuk</Link>
          </li>
        </ul>
      </div>

      <!-- Main Panel -->
      <div class="main-content">
        <h1 style="font-size: 1.875rem; font-weight: 800; margin-bottom: 1.5rem;">Dashboard Seller</h1>

        <!-- Stats Grid -->
        <div class="grid grid-cols-3" style="margin-bottom: 2rem;">
          <div class="card stat-card">
            <span class="stat-label">Total Produk</span>
            <span class="stat-value">{{ stats.totalProducts }}</span>
          </div>

          <div class="card stat-card">
            <span class="stat-label">Total Pesanan</span>
            <span class="stat-value">{{ stats.totalOrders }}</span>
          </div>

          <div class="card stat-card">
            <span class="stat-label">Total Pendapatan</span>
            <span class="stat-value color-primary">{{ formatRupiah(stats.totalEarnings) }}</span>
          </div>
        </div>

        <!-- Recent Orders -->
        <div class="card" style="padding: 1.5rem;">
          <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 1rem;">Pesanan Terbaru</h3>
          
          <div v-if="recentOrders.length > 0" class="table-responsive" style="box-shadow: none; border-radius: 0; border: none; margin-bottom: 0;">
            <table class="table">
              <thead>
                <tr>
                  <th>ID Pesanan</th>
                  <th>Pembeli</th>
                  <th>Tanggal</th>
                  <th>Total Transaksi</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="order in recentOrders" :key="order.id">
                  <td><strong style="font-family: monospace;">{{ order.order_number }}</strong></td>
                  <td>{{ order.user.name }}</td>
                  <td>{{ formatDate(order.created_at) }}</td>
                  <td><strong>{{ formatRupiah(order.total_amount) }}</strong></td>
                  <td>
                    <span class="badge" :class="getStatusClass(order.status)">
                      {{ formatStatus(order.status) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="no-recent-orders">
            <p style="color: var(--text-secondary); text-align: center; padding: 2rem 0;">Belum ada pesanan masuk.</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  stats: Object,
  recentOrders: Array
});

// Javascript route helper
const route = (name) => {
  const routes = {
    'seller.dashboard': '/seller/dashboard',
    'seller.products': '/seller/products',
    'seller.orders': '/seller/orders',
  };
  return routes[name] || '#';
};

const getStatusClass = (status) => {
  switch (status) {
    case 'pending': return 'badge-warning';
    case 'processed': return 'badge-primary';
    case 'shipped': return 'badge-info';
    case 'delivered': return 'badge-success';
    default: return 'badge-secondary';
  }
};

const formatStatus = (status) => {
  switch (status) {
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
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
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
.stat-card {
  padding: 1.5rem;
}
.stat-card:hover {
  transform: none;
  box-shadow: var(--shadow-sm);
  border-color: var(--border-color);
}
.stat-label {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--text-muted);
  letter-spacing: 0.05em;
  margin-bottom: 0.5rem;
}
.stat-value {
  font-size: 1.75rem;
  font-weight: 800;
}
.color-primary {
  color: var(--color-primary);
}
.no-recent-orders {
  border: 1px dashed var(--border-color);
  border-radius: 0.5rem;
}
.badge-info {
  background-color: #e0f2fe;
  color: #0284c7;
}
</style>
