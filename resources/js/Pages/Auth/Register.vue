<template>
  <AppLayout>
    <div class="auth-container">
      <div class="auth-card">
        <h2 class="auth-title">Daftar Akun Baru</h2>
        <p class="auth-subtitle">Bergabunglah dengan Brodev Online Store</p>

        <form @submit.prevent="submit" class="auth-form">
          <div class="form-group">
            <label class="form-label" for="name">Nama Lengkap</label>
            <input 
              id="name" 
              type="text" 
              class="form-control" 
              v-model="form.name" 
              required 
              autocomplete="name"
            />
            <span class="error-msg" v-if="form.errors.name">{{ form.errors.name }}</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Alamat Email</label>
            <input 
              id="email" 
              type="email" 
              class="form-control" 
              v-model="form.email" 
              required 
              autocomplete="email"
            />
            <span class="error-msg" v-if="form.errors.email">{{ form.errors.email }}</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="role">Daftar Sebagai</label>
            <select id="role" class="form-control form-select" v-model="form.role" required>
              <option value="buyer">Buyer (Pembeli)</option>
              <option value="seller">Seller (Penjual)</option>
            </select>
            <span class="error-msg" v-if="form.errors.role">{{ form.errors.role }}</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Kata Sandi</label>
            <input 
              id="password" 
              type="password" 
              class="form-control" 
              v-model="form.password" 
              required 
              autocomplete="new-password"
            />
            <span class="error-msg" v-if="form.errors.password">{{ form.errors.password }}</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi</label>
            <input 
              id="password_confirmation" 
              type="password" 
              class="form-control" 
              v-model="form.password_confirmation" 
              required 
              autocomplete="new-password"
            />
            <span class="error-msg" v-if="form.errors.password_confirmation">{{ form.errors.password_confirmation }}</span>
          </div>

          <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
            {{ form.processing ? 'Memproses...' : 'Daftar' }}
          </button>
        </form>

        <div class="auth-redirect">
          <span>Sudah punya akun? </span>
          <Link href="/login" class="auth-link">Masuk di Sini</Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  email: '',
  role: 'buyer',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<style scoped>
.auth-container {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2rem 0;
}
.auth-card {
  background-color: var(--bg-secondary);
  border: 1px solid var(--border-color);
  border-radius: 1rem;
  padding: 2.5rem;
  width: 100%;
  max-width: 450px;
  box-shadow: var(--shadow-md);
}
.auth-title {
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
  margin-bottom: 0.5rem;
}
.auth-subtitle {
  font-size: 0.875rem;
  color: var(--text-secondary);
  text-align: center;
  margin-bottom: 2rem;
}
.auth-form {
  margin-bottom: 1.5rem;
}
.error-msg {
  display: block;
  font-size: 0.8rem;
  color: var(--color-danger);
  margin-top: 0.25rem;
}
.w-full {
  width: 100%;
}
.auth-redirect {
  text-align: center;
  font-size: 0.9rem;
  color: var(--text-secondary);
}
.auth-link {
  color: var(--color-primary);
  font-weight: 600;
}
.auth-link:hover {
  text-decoration: underline;
}
</style>
