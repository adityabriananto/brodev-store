<template>
  <AppLayout>
    <div class="auth-container">
      <div class="auth-card">
        <h2 class="auth-title">Masuk ke Akun Anda</h2>
        <p class="auth-subtitle">Silakan masukkan email dan kata sandi Anda</p>

        <form @submit.prevent="submit" class="auth-form">
          <div class="form-group">
            <label class="form-label" for="email">Alamat Email</label>
            <input 
              id="email" 
              type="email" 
              class="form-control" 
              v-model="form.email" 
              required 
              autocomplete="username"
            />
            <span class="error-msg" v-if="form.errors.email">{{ form.errors.email }}</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Kata Sandi</label>
            <input 
              id="password" 
              type="password" 
              class="form-control" 
              v-model="form.password" 
              required 
              autocomplete="current-password"
            />
            <span class="error-msg" v-if="form.errors.password">{{ form.errors.password }}</span>
          </div>

          <button type="submit" class="btn btn-primary w-full" :disabled="form.processing">
            {{ form.processing ? 'Memproses...' : 'Masuk' }}
          </button>
        </form>

        <div class="auth-redirect">
          <span>Belum punya akun? </span>
          <Link href="/register" class="auth-link">Daftar Sekarang</Link>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
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
  max-width: 420px;
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
