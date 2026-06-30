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

        <!-- Bank Account Settings Card -->
        <div class="card" style="padding: 1.5rem; margin-top: 2rem;">
          <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem;">Pengaturan Rekening Bank</h3>
          <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1.5rem;">
            Lengkapi detail rekening bank di bawah ini agar pembeli dapat melakukan pembayaran transfer bank secara langsung ke rekening Anda.
          </p>

          <form @submit.prevent="submitBankDetails">
            <div class="grid grid-cols-3" style="gap: 1.5rem;">
              <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="bank_name">Nama Bank</label>
                <input 
                  type="text" 
                  id="bank_name" 
                  class="form-control" 
                  v-model="form.bank_name" 
                  placeholder="Contoh: BCA, Mandiri, BNI"
                  required
                />
                <span class="error-msg" v-if="form.errors.bank_name">{{ form.errors.bank_name }}</span>
              </div>

              <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="bank_account_number">Nomor Rekening</label>
                <input 
                  type="text" 
                  id="bank_account_number" 
                  class="form-control" 
                  v-model="form.bank_account_number" 
                  placeholder="Masukkan nomor rekening"
                  required
                />
                <span class="error-msg" v-if="form.errors.bank_account_number">{{ form.errors.bank_account_number }}</span>
              </div>

              <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="bank_account_holder">Nama Pemilik Rekening</label>
                <input 
                  type="text" 
                  id="bank_account_holder" 
                  class="form-control" 
                  v-model="form.bank_account_holder" 
                  placeholder="Nama pemilik rekening"
                  required
                />
                <span class="error-msg" v-if="form.errors.bank_account_holder">{{ form.errors.bank_account_holder }}</span>
              </div>
            </div>

            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 1rem; margin-top: 1.5rem;">
              <span v-if="form.recentlySuccessful" style="color: var(--color-success); font-size: 0.875rem; font-weight: 600;">
                ✓ Rekening bank berhasil disimpan.
              </span>
              <button 
                type="submit" 
                class="btn btn-primary" 
                :disabled="form.processing"
                style="padding: 0.5rem 1.5rem;"
              >
                {{ form.processing ? 'Menyimpan...' : 'Simpan Rekening' }}
              </button>
            </div>
          </form>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
  stats: Object,
  recentOrders: Array,
  seller: Object
});

const form = useForm({
  bank_name: props.seller?.bank_name || '',
  bank_account_number: props.seller?.bank_account_number || '',
  bank_account_holder: props.seller?.bank_account_holder || '',
});

const submitBankDetails = () => {
  form.post(route('seller.bank-account.update'), {
    preserveScroll: true,
  });
};

// Javascript route helper
const route = (name) => {
  const routes = {
    'seller.dashboard': '/seller/dashboard',
    'seller.products': '/seller/products',
    'seller.orders': '/seller/orders',
    'seller.bank-account.update': '/seller/bank-account',
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
