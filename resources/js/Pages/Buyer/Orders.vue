<template>
  <AppLayout>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
      <h1 style="font-size: 1.875rem; font-weight: 800; margin: 0;">Pesanan Saya</h1>
      
      <!-- Status Filter -->
      <div style="display: flex; align-items: center; gap: 0.5rem;">
        <label style="font-size: 0.72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">Status:</label>
        <select 
          class="form-control form-select" 
          style="min-width: 180px; font-size: 0.875rem; padding: 0.35rem 0.75rem; height: auto;"
          :value="filters.status || ''"
          @change="filterByStatus($event.target.value)"
        >
          <option value="">Semua Status</option>
          <option value="unpaid">Menunggu Pembayaran</option>
          <option value="pending">Menunggu Konfirmasi</option>
          <option value="processed">Diproses</option>
          <option value="shipped">Dikirim</option>
          <option value="delivered">Diterima</option>
        </select>
      </div>
    </div>

    <div v-if="orders.length > 0" class="orders-list">
      <div v-for="order in orders" :key="order.id" class="card order-card">
        <!-- Order Header -->
        <div class="order-header-row">
          <div class="order-id-date">
            <span class="order-id" style="font-family: monospace;">{{ order.order_number }}</span>
            <span class="order-date">{{ formatDate(order.created_at) }}</span>
          </div>
          <div class="order-status-badge">
            <span class="badge" :class="getStatusClass(order.status)">
              {{ formatStatus(order.status) }}
            </span>
          </div>
        </div>

        <div class="order-details-grid">
          <!-- Order Items -->
          <div class="order-items-list">
            <div v-for="item in order.items" :key="item.id" class="ordered-item-row">
              <img :src="item.product.image_path || '/images/placeholder.png'" :alt="item.product.name" class="ordered-item-img" />
              <div class="ordered-item-info">
                <h4 class="ordered-item-name">{{ item.product.name }}</h4>
                <p class="ordered-item-seller">Penjual: {{ item.product.seller.name }}</p>
                <span class="ordered-item-price-qty">{{ item.quantity }} x {{ formatRupiah(item.price) }}</span>
              </div>
            </div>
          </div>

          <!-- Order Summary and Address -->
          <div class="order-shipping-summary">
            <div class="summary-section">
              <span class="sec-label">Alamat Pengiriman:</span>
              <p class="sec-val">{{ order.shipping_address }}</p>
            </div>
            
            <div class="summary-section" style="margin-top: 1rem;">
              <span class="sec-label">Metode Pembayaran:</span>
              <p class="sec-val"><strong>{{ order.payment_method }}</strong></p>
            </div>

            <div class="summary-section" style="margin-top: 1rem;" v-if="order.tracking_number">
              <span class="sec-label">Nomor Resi (Tracking):</span>
              <p class="sec-val" style="font-family: monospace; font-weight: 700; color: var(--color-primary);">{{ order.tracking_number }}</p>
            </div>

            <div class="summary-section" style="margin-top: 1rem; border-top: 1px dashed var(--border-color); padding-top: 0.75rem;">
              <span class="sec-label">Total Pembayaran:</span>
              <p class="total-payment">{{ formatRupiah(order.total_amount) }}</p>
            </div>
          </div>

          <!-- Status History Timeline -->
          <div class="status-timeline" v-if="order.status_histories && order.status_histories.length">
            <span class="sec-label" style="display:block; margin-bottom: 0.75rem;">Riwayat Status</span>
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
                  <span class="timeline-time">{{ formatDate(history.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Proof Upload Panel -->
        <div class="payment-proof-panel" v-if="order.status === 'unpaid'" style="flex-direction: column; align-items: stretch; gap: 1rem;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1.25rem;">
            <div class="proof-info">
              <h4 class="proof-title">💡 Pembayaran Transfer Bank Belum Selesai</h4>
              <p class="proof-desc">Silakan lakukan transfer sesuai rekening penjual di bawah ini, lalu unggah bukti transfer agar pesanan Anda dapat dikonfirmasi dan diproses penjual.</p>
            </div>
            <form @submit.prevent="submitPaymentProof(order.id)" class="proof-form">
              <div class="file-input-wrapper">
                <input 
                  type="file" 
                  :id="'proof-' + order.id" 
                  class="form-control file-input" 
                  accept="image/*"
                  @change="handleFileChange($event, order.id)"
                  required
                />
              </div>
              <button type="submit" class="btn btn-primary btn-sm" :disabled="uploadingId === order.id" style="height: auto; padding: 0.5rem 1rem;">
                {{ uploadingId === order.id ? 'Mengunggah...' : 'Kirim Bukti Pembayaran' }}
              </button>
            </form>
          </div>

          <!-- Bank details list -->
          <div style="background-color: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem; margin-top: 0.5rem;">
            <h5 style="font-size: 0.85rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.05em;">Detail Rekening Penjual:</h5>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
              <div v-for="(sellerGroup, idx) in getOrderSellersBreakdown(order)" :key="sellerGroup.sellerId" :style="idx !== getOrderSellersBreakdown(order).length - 1 ? { borderBottom: '1px solid var(--border-color)', paddingBottom: '0.75rem' } : {}">
                <div style="font-weight: 600; font-size: 0.875rem; color: var(--text-primary); margin-bottom: 0.25rem;">
                  Toko: {{ sellerGroup.sellerName }}
                </div>
                <div v-if="sellerGroup.hasBankAccount" style="font-size: 0.82rem; color: var(--text-secondary); display: flex; flex-wrap: wrap; gap: 1rem;">
                  <div>Bank: <strong style="color: var(--text-primary);">{{ sellerGroup.bankName }}</strong></div>
                  <div>No. Rekening: <strong style="color: var(--text-primary); font-family: monospace;">{{ sellerGroup.bankAccountNumber }}</strong></div>
                  <div>Atas Nama: <strong style="color: var(--text-primary);">{{ sellerGroup.bankAccountHolder }}</strong></div>
                  <div>Jumlah Transfer: <strong style="color: var(--color-primary); font-weight: 700;">{{ formatRupiah(sellerGroup.subtotal) }}</strong></div>
                </div>
                <div v-else style="font-size: 0.82rem; color: var(--color-danger); font-weight: 500;">
                  ⚠ Penjual ini belum mengatur rekening bank. Hubungi penjual atau admin.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="empty-orders">
      <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--text-muted); margin-bottom: 1.5rem;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      <h2>Belum Ada Pesanan</h2>
      <p style="color: var(--text-secondary); margin-bottom: 1.5rem; margin-top: 0.5rem;">Anda belum pernah melakukan pemesanan produk.</p>
      <Link href="/" class="btn btn-primary">Mulai Belanja</Link>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
  orders: Array,
  filters: Object
});

const filterByStatus = (status) => {
  router.get('/orders', {
    status: status || undefined
  }, {
    preserveState: true,
    replace: true
  });
};

const selectedFiles = ref({});
const uploadingId = ref(null);

const handleFileChange = (event, orderId) => {
  const file = event.target.files[0];
  if (file) {
    selectedFiles.value[orderId] = file;
  }
};

const submitPaymentProof = (orderId) => {
  const file = selectedFiles.value[orderId];
  if (!file) {
    alert('Silakan pilih file bukti pembayaran terlebih dahulu!');
    return;
  }

  uploadingId.value = orderId;

  const formData = new FormData();
  formData.append('payment_proof', file);

  router.post(`/orders/${orderId}/payment-proof`, formData, {
    forceFormData: true,
    onFinish: () => {
      uploadingId.value = null;
      delete selectedFiles.value[orderId];
    },
    onSuccess: () => {
      alert('Bukti pembayaran berhasil dikirim!');
    },
    onError: (errors) => {
      alert(errors.payment_proof || errors.error || 'Gagal mengirim bukti pembayaran.');
    }
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
    year: 'numeric',
    month: 'long',
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

const getOrderSellersBreakdown = (order) => {
  const breakdown = {};
  order.items.forEach(item => {
    const seller = item.product?.seller;
    if (!seller) return;
    const sellerId = seller.id;
    if (!breakdown[sellerId]) {
      breakdown[sellerId] = {
        sellerId: sellerId,
        sellerName: seller.name,
        bankName: seller.bank_name || '',
        bankAccountNumber: seller.bank_account_number || '',
        bankAccountHolder: seller.bank_account_holder || '',
        hasBankAccount: !!(seller.bank_name && seller.bank_account_number && seller.bank_account_holder),
        subtotal: 0,
      };
    }
    breakdown[sellerId].subtotal += item.price * item.quantity;
  });
  return Object.values(breakdown);
};</script>

<style scoped>
.orders-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.order-card {
  padding: 1.5rem;
}
.order-card:hover {
  transform: none;
  box-shadow: var(--shadow-sm);
}
.order-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
  padding-bottom: 1rem;
  margin-bottom: 1.25rem;
}
.order-id {
  font-size: 1.15rem;
  font-weight: 700;
  margin-right: 1rem;
}
.order-date {
  font-size: 0.85rem;
  color: var(--text-secondary);
}
.order-details-grid {
  display: flex;
  gap: 2rem;
  flex-wrap: wrap;
}
.order-items-list {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  border-right: 1px solid var(--border-color);
  padding-right: 2rem;
}
.ordered-item-row {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.ordered-item-img {
  width: 55px;
  height: 55px;
  object-fit: cover;
  border-radius: 0.375rem;
  border: 1px solid var(--border-color);
}
.ordered-item-info {
  flex-grow: 1;
}
.ordered-item-name {
  font-size: 0.95rem;
  font-weight: 600;
}
.ordered-item-seller {
  font-size: 0.75rem;
  color: var(--text-secondary);
}
.ordered-item-price-qty {
  font-size: 0.85rem;
  font-weight: 500;
}
.order-shipping-summary {
  width: 250px;
  flex-shrink: 0;
}
.status-timeline {
  flex-shrink: 0;
  min-width: 220px;
  border-left: 1px solid var(--border-color);
  padding-left: 1.5rem;
}
.timeline {
  display: flex;
  flex-direction: column;
  gap: 0;
}
.timeline-item {
  display: flex;
  gap: 0.75rem;
  position: relative;
  padding-bottom: 1rem;
}
.timeline-item:not(.timeline-last)::before {
  content: '';
  position: absolute;
  left: 7px;
  top: 16px;
  bottom: 0;
  width: 2px;
  background: var(--border-color);
}
.timeline-dot {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 2px;
  border: 2px solid transparent;
}
.dot-danger   { background: #ef4444; border-color: #fca5a5; }
.dot-warning  { background: #f59e0b; border-color: #fde68a; }
.dot-primary  { background: var(--color-primary); border-color: var(--color-primary-light, #c7d2fe); }
.dot-info     { background: #0ea5e9; border-color: #bae6fd; }
.dot-success  { background: #22c55e; border-color: #bbf7d0; }
.timeline-content {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

/* Payment Proof Panel */
.payment-proof-panel {
  margin-top: 1.5rem;
  padding: 1.25rem 1.5rem;
  background-color: var(--bg-tertiary, #f8fafc);
  border: 1px solid var(--border-color);
  border-radius: 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.25rem;
}
.proof-info {
  flex: 1;
  min-width: 280px;
}
.proof-title {
  font-size: 0.95rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
  color: var(--text-primary);
}
.proof-desc {
  font-size: 0.8rem;
  color: var(--text-secondary);
  line-height: 1.4;
}
.proof-form {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.file-input-wrapper {
  min-width: 200px;
}
.file-input {
  font-size: 0.82rem;
  height: 2.2rem;
  padding: 0.3rem 0.5rem;
}
.timeline-status {
  font-size: 0.85rem;
  font-weight: 700;
}
.timeline-note {
  font-size: 0.78rem;
  color: var(--text-secondary);
  line-height: 1.35;
}
.timeline-time {
  font-size: 0.72rem;
  color: var(--text-muted);
}
.sec-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 0.25rem;
}
.sec-val {
  font-size: 0.9rem;
  color: var(--text-secondary);
  line-height: 1.4;
}
.total-payment {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--color-primary);
}
.empty-orders {
  text-align: center;
  padding: 5rem 2rem;
  background-color: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 1rem;
}
.badge-info {
  background-color: #e0f2fe;
  color: #0284c7;
}

@media (max-width: 900px) {
  .order-details-grid {
    flex-direction: column;
    gap: 1.5rem;
  }
  .order-items-list {
    border-right: none;
    padding-right: 0;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 1.5rem;
  }
  .order-shipping-summary {
    width: 100%;
  }
  .status-timeline {
    border-left: none;
    padding-left: 0;
    border-top: 1px solid var(--border-color);
    padding-top: 1.5rem;
    min-width: 100%;
  }
}
</style>
