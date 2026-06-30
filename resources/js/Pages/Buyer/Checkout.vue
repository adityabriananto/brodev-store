<template>
  <AppLayout>
    <div style="margin-bottom: 1.5rem;">
      <Link href="/cart" class="btn btn-secondary btn-sm">
        &larr; Kembali ke Keranjang
      </Link>
    </div>

    <h1 style="font-size: 1.875rem; font-weight: 800; margin-bottom: 1.5rem;">Checkout</h1>

    <div class="checkout-layout">
      <!-- Checkout Form -->
      <div class="checkout-form-panel">
        <div class="card" style="padding: 2rem; margin-bottom: 1.5rem;">
          <h3 style="font-size: 1.2rem; font-weight: 700; margin-bottom: 1.5rem;">Alamat Pengiriman & Pembayaran</h3>
          
          <form @submit.prevent="submit">
            <!-- Checkout Error -->
            <div class="alert alert-danger" v-if="form.errors.checkout">
              <span>{{ form.errors.checkout }}</span>
            </div>

            <div class="form-group">
              <label class="form-label" for="address">Alamat Lengkap</label>
              <textarea 
                id="address" 
                class="form-control" 
                v-model="form.shipping_address" 
                rows="4" 
                placeholder="Masukkan alamat pengiriman lengkap Anda (Jalan, No. Rumah, RT/RW, Kecamatan, Kota, Kode Pos)"
                required
              ></textarea>
              <span class="error-msg" v-if="form.errors.shipping_address">{{ form.errors.shipping_address }}</span>
            </div>

            <div class="form-group">
              <label class="form-label" for="payment">Metode Pembayaran</label>
              <select id="payment" class="form-control form-select" v-model="form.payment_method" required>
                <option value="COD">COD (Bayar di Tempat)</option>
                <option value="Transfer Bank">Transfer Bank</option>
              </select>
              <span class="error-msg" v-if="form.errors.payment_method">{{ form.errors.payment_method }}</span>
            </div>

            <!-- Bank Transfer Details Info Box -->
            <div v-if="form.payment_method === 'Transfer Bank'" style="margin-top: 1.5rem; margin-bottom: 1.5rem; padding: 1.25rem; background-color: var(--bg-tertiary); border: 1px solid var(--border-color); border-radius: 0.5rem;">
              <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>🏦 Detail Transfer Bank</span>
              </h4>
              <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem; line-height: 1.4;">
                Silakan transfer sesuai detail rekening penjual berikut sebelum membuat pesanan:
              </p>

              <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div v-for="(sellerGroup, idx) in sellersBreakdown" :key="sellerGroup.sellerId" :style="idx !== sellersBreakdown.length - 1 ? { borderBottom: '1px solid var(--border-color)', paddingBottom: '0.75rem' } : {}">
                  <div style="font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem; color: var(--text-primary);">
                    Toko: {{ sellerGroup.sellerName }}
                  </div>
                  <div v-if="sellerGroup.hasBankAccount" style="font-size: 0.85rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 0.25rem;">
                    <div>Bank: <strong style="color: var(--text-primary);">{{ sellerGroup.bankName }}</strong></div>
                    <div>No. Rekening: <strong style="color: var(--text-primary); font-family: monospace;">{{ sellerGroup.bankAccountNumber }}</strong></div>
                    <div>Atas Nama: <strong style="color: var(--text-primary);">{{ sellerGroup.bankAccountHolder }}</strong></div>
                    <div>Jumlah Transfer: <strong style="color: var(--color-primary); font-size: 0.95rem;">{{ formatRupiah(sellerGroup.subtotal) }}</strong></div>
                  </div>
                  <div v-else style="font-size: 0.85rem; color: var(--color-danger); font-weight: 500;">
                    ⚠ Penjual ini belum mengatur rekening bank. Hubungi penjual atau admin.
                  </div>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; margin-top: 1rem;" :disabled="form.processing">
              {{ form.processing ? 'Memproses Pesanan...' : 'Buat Pesanan Sekarang' }}
            </button>
          </form>
        </div>
      </div>

      <!-- Order Summary -->
      <div class="order-summary-panel">
        <div class="card" style="padding: 1.5rem;">
          <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 1.25rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            Detail Pesanan
          </h3>

          <div class="items-preview-list">
            <div v-for="item in cartItems" :key="item.id" class="item-preview-row">
              <div class="item-preview-detail">
                <span class="item-preview-name">{{ item.product.name }}</span>
                <span class="item-preview-qty">Qty: {{ item.quantity }}</span>
              </div>
              <span class="item-preview-price">{{ formatRupiah(item.product.price * item.quantity) }}</span>
            </div>
          </div>

          <div style="height: 1px; background-color: var(--border-color); margin: 1rem 0;"></div>

          <div class="summary-row">
            <span>Total Item</span>
            <span>{{ totalQuantity }}</span>
          </div>
          <div class="summary-row" style="font-weight: 700; font-size: 1.1rem; border-top: 1px dashed var(--border-color); padding-top: 1rem; margin-top: 1rem;">
            <span>Total Bayar</span>
            <span style="color: var(--color-primary);">{{ formatRupiah(totalPrice) }}</span>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
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

const sellersBreakdown = computed(() => {
  const breakdown = {};
  props.cartItems.forEach(item => {
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
    breakdown[sellerId].subtotal += item.product.price * item.quantity;
  });
  return Object.values(breakdown);
});

const form = useForm({
  shipping_address: '',
  payment_method: 'COD',
});

const submit = () => {
  form.post('/checkout');
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
.checkout-layout {
  display: flex;
  gap: 2rem;
  align-items: flex-start;
}
.checkout-form-panel {
  flex-grow: 1;
}
.order-summary-panel {
  width: 340px;
  flex-shrink: 0;
  position: sticky;
  top: 6rem;
}
.items-preview-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.item-preview-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.9rem;
}
.item-preview-detail {
  display: flex;
  flex-direction: column;
  max-width: 70%;
}
.item-preview-name {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.item-preview-qty {
  font-size: 0.75rem;
  color: var(--text-secondary);
}
.item-preview-price {
  font-weight: 600;
}
.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  font-size: 0.95rem;
  color: var(--text-secondary);
}
.error-msg {
  display: block;
  font-size: 0.8rem;
  color: var(--color-danger);
  margin-top: 0.25rem;
}

@media (max-width: 900px) {
  .checkout-layout {
    flex-direction: column;
  }
  .order-summary-panel {
    width: 100%;
    position: static;
  }
}
</style>
