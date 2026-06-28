<template>
  <div>
    <!-- Page Loading Progress Bar -->
    <div class="progress-bar" :class="{ active: isNavigating }"></div>

    <!-- Navigation Header -->
    <header class="header">
      <div class="container nav-wrapper">
        <Link href="/" class="logo-brand">
          <span>Brodev</span>
        </Link>

        <!-- Search Bar (Show only on Home Catalog or when browsing) -->
        <div class="search-bar-container" v-if="showSearch">
          <input 
            type="text" 
            placeholder="Cari produk..." 
            class="form-control search-input" 
            v-model="searchQuery"
            @keyup.enter="handleSearch"
          />
        </div>

        <nav class="nav-links">
          <!-- Common Guest / Buyer Links -->
          <Link href="/" class="nav-item" :class="{ active: $page.component === 'Buyer/Home' }">Katalog</Link>

          <!-- Superadmin Role Toggle -->
          <div v-if="user && user.role === 'superadmin'" class="role-selector">
            <span class="role-label">Mode:</span>
            <button 
              class="btn btn-sm" 
              :class="activeMode === 'buyer' ? 'btn-primary' : 'btn-secondary'"
              @click="switchMode('buyer')"
            >
              Buyer
            </button>
            <button 
              class="btn btn-sm" 
              :class="activeMode === 'seller' ? 'btn-primary' : 'btn-secondary'"
              @click="switchMode('seller')"
            >
              Seller
            </button>
          </div>

          <!-- Conditional Links based on Role / Active Mode -->
          <template v-if="user">
            <!-- Seller Section -->
            <template v-if="user.role === 'seller' || (user.role === 'superadmin' && activeMode === 'seller')">
              <Link :href="route('seller.dashboard')" class="nav-item" :class="{ active: $page.component.startsWith('Seller/') }">
                Seller Panel
              </Link>
            </template>

            <!-- Buyer Section -->
            <template v-if="user.role === 'buyer' || (user.role === 'superadmin' && activeMode === 'buyer')">
              <Link :href="route('cart.index')" class="nav-item cart-badge" :class="{ active: $page.component === 'Buyer/Cart' }">
                Keranjang
                <span class="cart-count" v-if="cartItemsCount > 0">{{ cartItemsCount }}</span>
              </Link>
              <Link :href="route('orders.index')" class="nav-item" :class="{ active: $page.component === 'Buyer/Orders' }">
                Pesanan Saya
              </Link>
            </template>

            <!-- User Greeting & Logout -->
            <span class="user-greeting">Halo, <strong>{{ user.name }}</strong></span>
            <button @click="logout" class="btn btn-secondary btn-sm">Keluar</button>
          </template>

          <template v-else>
            <Link href="/login" class="btn btn-secondary btn-sm">Masuk</Link>
            <Link href="/register" class="btn btn-primary btn-sm">Daftar</Link>
          </template>

          <!-- Theme Toggle Button -->
          <button @click="toggleTheme" class="btn-icon" aria-label="Toggle Theme">
            <svg v-if="theme === 'light'" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M20 12h2"/><path d="M2 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
          </button>
        </nav>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="container" style="padding-top: 2rem; padding-bottom: 3rem; flex-grow: 1;">
      <!-- Flash Alert Notifications -->
      <div v-if="$page.props.flash.success" class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>
        <span>{{ $page.props.flash.success }}</span>
      </div>
      <div v-if="$page.props.flash.error" class="alert alert-danger">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>
        <span>{{ $page.props.flash.error }}</span>
      </div>

      <Transition name="page" mode="out-in">
        <div :key="$page.component">
          <slot></slot>
        </div>
      </Transition>
    </main>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <p>&copy; 2026 Brodev - Online Store. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';

const page = usePage();

const isNavigating = ref(false);

// Smooth navigation progress bar
router.on('start', () => { isNavigating.value = true; });
router.on('finish', () => { isNavigating.value = false; });

const searchQuery = ref('');
const showSearch = computed(() => page.component === 'Buyer/Home');

const user = computed(() => page.props.auth.user);

// Dynamic search trigger
const handleSearch = () => {
  router.get('/', { search: searchQuery.value }, { preserveState: true });
};

// Theme System
const theme = ref('light');
const toggleTheme = () => {
  theme.value = theme.value === 'light' ? 'dark' : 'light';
  document.documentElement.setAttribute('data-theme', theme.value);
  localStorage.setItem('theme', theme.value);
};

// Superadmin Mode System
const activeMode = ref('buyer');
const switchMode = (mode) => {
  activeMode.value = mode;
  localStorage.setItem('superadmin_mode', mode);
  
  if (mode === 'seller') {
    router.visit(route('seller.dashboard'));
  } else {
    router.visit(route('home'));
  }
};

// Initialize theme and superadmin mode on mount
onMounted(() => {
  // Theme
  const savedTheme = localStorage.getItem('theme') || 'light';
  theme.value = savedTheme;
  document.documentElement.setAttribute('data-theme', savedTheme);

  // Superadmin mode
  if (user.value && user.value.role === 'superadmin') {
    const savedMode = localStorage.getItem('superadmin_mode') || 'buyer';
    activeMode.value = savedMode;
  }
  
  // Search query sync
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('search')) {
    searchQuery.value = urlParams.get('search');
  }
});

// Javascript route helper
const route = (name, params) => {
  const routes = {
    'home': '/',
    'login': '/login',
    'register': '/register',
    'logout': '/logout',
    'cart.index': '/cart',
    'cart.store': '/cart',
    'checkout.show': '/checkout',
    'checkout.store': '/checkout',
    'orders.index': '/orders',
    'seller.dashboard': '/seller/dashboard',
    'seller.products': '/seller/products',
    'seller.orders': '/seller/orders',
  };
  
  let url = routes[name] || '#';
  if (params) {
    Object.keys(params).forEach(key => {
      url = url.replace(`{${key}}`, params[key]);
    });
  }
  return url;
};

// Cart Items Count
const cartItemsCount = computed(() => {
  return page.props.cartCount || 0;
});

const logout = () => {
  router.post(route('logout'));
};
</script>

<style scoped>
.progress-bar {
  position: fixed;
  top: 0;
  left: 0;
  height: 3px;
  width: 0%;
  background: var(--color-primary);
  z-index: 9999;
  transition: width 0.1s ease;
  box-shadow: 0 0 8px var(--color-primary);
}
.progress-bar.active {
  animation: progressAnim 1.2s ease-in-out infinite;
}
@keyframes progressAnim {
  0%   { width: 0%; opacity: 1; }
  70%  { width: 85%; opacity: 1; }
  100% { width: 95%; opacity: 0.8; }
}

.search-bar-container {
  flex-grow: 1;
  max-width: 320px;
  margin: 0 1.5rem;
}
.search-input {
  width: 100%;
  height: 2.25rem;
  padding: 0 0.75rem;
  font-size: 0.875rem;
  border-radius: 0.375rem;
}
.role-selector {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  background-color: var(--bg-tertiary);
  padding: 0.15rem 0.35rem;
  border-radius: 0.375rem;
  border: 1px solid var(--border-color);
}
.role-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-secondary);
  margin-right: 0.25rem;
}
.user-greeting {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

/* Page Transition */
.page-enter-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.page-leave-active {
  transition: opacity 0.12s ease, transform 0.12s ease;
}
.page-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
.page-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (max-width: 768px) {
  .nav-wrapper {
    flex-direction: column;
    height: auto;
    padding: 1rem 0;
    gap: 0.75rem;
  }
  .search-bar-container {
    margin: 0;
    max-width: 100%;
    width: 100%;
  }
  .nav-links {
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.75rem;
  }
}
</style>
