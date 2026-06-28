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
          <li class="sidebar-item active">
            <Link :href="route('seller.products')">Produk Saya</Link>
          </li>
          <li class="sidebar-item">
            <Link :href="route('seller.orders')">Pesanan Masuk</Link>
          </li>
        </ul>
      </div>

      <!-- Main Panel -->
      <div class="main-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
          <h1 style="font-size: 1.875rem; font-weight: 800;">Kelola Produk</h1>
          <button class="btn btn-primary btn-sm" @click="openAddModal">
            + Tambah Produk
          </button>
        </div>

        <!-- Products Table -->
        <div v-if="products.length > 0" class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 80px;">Foto</th>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th style="width: 100px;">Stok</th>
                <th v-if="$page.props.auth.user.role === 'superadmin'">Penjual</th>
                <th style="width: 150px; text-align: right;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="product in products" :key="product.id">
                <td>
                  <img :src="product.image_path || '/images/placeholder.png'" :alt="product.name" class="table-product-img" />
                </td>
                <td>
                  <div class="table-product-title">{{ product.name }}</div>
                  <div class="table-product-desc">{{ product.description }}</div>
                </td>
                <td><strong>{{ formatRupiah(product.price) }}</strong></td>
                <td>
                  <span class="badge" :class="product.stock > 5 ? 'badge-success' : (product.stock > 0 ? 'badge-warning' : 'badge-danger')">
                    {{ product.stock }}
                  </span>
                </td>
                <td v-if="$page.props.auth.user.role === 'superadmin'">
                  <span class="badge badge-primary">{{ product.seller.name }}</span>
                </td>
                <td style="text-align: right;">
                  <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                    <button class="btn btn-secondary btn-sm" @click="openEditModal(product)">Edit</button>
                    <button class="btn btn-danger btn-sm" @click="deleteProduct(product.id)">Hapus</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="no-products">
          <p style="color: var(--text-secondary); text-align: center; padding: 4rem 0;">Belum ada produk yang dijual. Klik Tambah Produk untuk memulai.</p>
        </div>
      </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div class="modal-overlay" v-if="showModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3 class="modal-title">{{ isEdit ? 'Edit Produk' : 'Tambah Produk Baru' }}</h3>
          <button class="modal-close" @click="closeModal">&times;</button>
        </div>

        <form @submit.prevent="submitForm">
          <div class="form-group">
            <label class="form-label" for="name">Nama Produk</label>
            <input id="name" type="text" class="form-control" v-model="form.name" required />
            <span class="error-msg" v-if="form.errors.name">{{ form.errors.name }}</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="description">Deskripsi</label>
            <textarea id="description" class="form-control" v-model="form.description" rows="3"></textarea>
            <span class="error-msg" v-if="form.errors.description">{{ form.errors.description }}</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="price">Harga (IDR)</label>
            <input id="price" type="number" class="form-control" v-model="form.price" min="0" required />
            <span class="error-msg" v-if="form.errors.price">{{ form.errors.price }}</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="stock">Stok</label>
            <input id="stock" type="number" class="form-control" v-model="form.stock" min="0" required />
            <span class="error-msg" v-if="form.errors.stock">{{ form.errors.stock }}</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="image">Foto Produk (Maks 2MB)</label>
            <input id="image" type="file" class="form-control" @input="form.image = $event.target.files[0]" accept="image/*" />
            <span class="error-msg" v-if="form.errors.image">{{ form.errors.image }}</span>
            <div class="image-preview-wrapper" v-if="imagePreview || form.image_path">
              <span class="preview-label">Pratinjau:</span>
              <img :src="imagePreview || form.image_path" class="form-image-preview" />
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" @click="closeModal">Batal</button>
            <button type="submit" class="btn btn-primary btn-sm" :disabled="form.processing">
              {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

defineProps({
  products: Array
});

// Javascript route helper
const route = (name, params) => {
  const routes = {
    'seller.dashboard': '/seller/dashboard',
    'seller.products': '/seller/products',
    'seller.orders': '/seller/orders',
    'seller.store': '/seller/products',
    'seller.update': `/seller/products/${params?.product}`,
    'seller.destroy': `/seller/products/${params?.product}`,
  };
  return routes[name] || '#';
};

const showModal = ref(false);
const isEdit = ref(false);
const editProductId = ref(null);
const imagePreview = ref(null);

const form = useForm({
  name: '',
  description: '',
  price: '',
  stock: '',
  image: null,
  image_path: null,
});

// Watch image upload to show preview
watch(() => form.image, (file) => {
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    imagePreview.value = null;
  }
});

const openAddModal = () => {
  form.reset();
  form.clearErrors();
  imagePreview.value = null;
  isEdit.value = false;
  showModal.value = true;
};

const openEditModal = (product) => {
  form.clearErrors();
  imagePreview.value = null;
  isEdit.value = true;
  editProductId.value = product.id;
  
  form.name = product.name;
  form.description = product.description || '';
  form.price = product.price;
  form.stock = product.stock;
  form.image = null;
  form.image_path = product.image_path;

  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.reset();
};

const submitForm = () => {
  if (isEdit.value) {
    form.post(route('seller.update', { product: editProductId.value }), {
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(route('seller.store'), {
      onSuccess: () => closeModal(),
    });
  }
};

const deleteProduct = (id) => {
  if (confirm('Apakah Anda yakin ingin menghapus produk ini? Semua data keranjang & pesanan pembeli terkait akan terpengaruh.')) {
    router.delete(route('seller.destroy', { product: id }));
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
.table-product-img {
  width: 50px;
  height: 50px;
  object-fit: cover;
  border-radius: 0.375rem;
  border: 1px solid var(--border-color);
}
.table-product-title {
  font-weight: 700;
  font-size: 0.95rem;
}
.table-product-desc {
  font-size: 0.8rem;
  color: var(--text-secondary);
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 350px;
}
.no-products {
  background-color: var(--bg-secondary);
  border: 1px dashed var(--border-color);
  border-radius: 1rem;
}
.error-msg {
  display: block;
  font-size: 0.8rem;
  color: var(--color-danger);
  margin-top: 0.25rem;
}
.image-preview-wrapper {
  margin-top: 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.preview-label {
  font-size: 0.75rem;
  color: var(--text-secondary);
}
.form-image-preview {
  width: 120px;
  height: 90px;
  object-fit: cover;
  border-radius: 0.375rem;
  border: 1px solid var(--border-color);
}
</style>
