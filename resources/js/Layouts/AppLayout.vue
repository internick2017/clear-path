
<template>
  <div class="min-h-screen bg-gray-100 font-sans antialiased">
    <!-- Mobile Navigation Overlay -->
    <div v-if="mobileMenuOpen" 
         class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
         @click="mobileMenuOpen = false">
    </div>

    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <!-- Logo and Desktop Navigation -->
          <div class="flex items-center">
            <a href="/dashboard" class="font-bold text-lg text-gray-800 flex items-center">
              <svg class="w-8 h-8 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
              Clear Path
            </a>
            
            <!-- Desktop Navigation Links -->
            <div class="hidden lg:flex items-center space-x-8 ml-10">
              <a href="/dashboard" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Dashboard</a>
              <a href="/transactions" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Transacciones</a>
              <a href="/budgets" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Presupuestos</a>
              <a href="/goals" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Metas</a>
              <a href="/debts" class="text-gray-700 hover:text-blue-600 font-medium transition-colors">Deudas</a>
            </div>
          </div>

          <!-- Right side - User menu and mobile button -->
          <div class="flex items-center space-x-4">
            <!-- Notification Dropdown (Desktop) -->
            <div class="hidden lg:block">
              <NotificationDropdown :notifications="notifications" />
            </div>

            <!-- User Menu (Desktop) -->
            <div class="hidden lg:flex items-center space-x-4">
              <span class="text-gray-600 text-sm">{{ $page.props.auth?.user?.name }}</span>
              <form method="POST" :action="route('logout')" class="inline">
                <button type="submit" class="text-gray-700 hover:text-red-600 text-sm font-medium transition-colors">
                  Salir
                </button>
              </form>
            </div>

            <!-- Mobile menu button -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" 
                    class="lg:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
              <svg class="h-6 w-6" :class="{ 'hidden': mobileMenuOpen, 'block': !mobileMenuOpen }" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg class="h-6 w-6" :class="{ 'block': mobileMenuOpen, 'hidden': !mobileMenuOpen }" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Mobile Navigation Menu -->
      <div v-if="mobileMenuOpen" class="lg:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t border-gray-200">
          <a href="/dashboard" 
             class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-colors">
            Dashboard
          </a>
          <a href="/transactions" 
             class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-colors">
            Transacciones
          </a>
          <a href="/budgets" 
             class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-colors">
            Presupuestos
          </a>
          <a href="/goals" 
             class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-colors">
            Metas
          </a>
          <a href="/debts" 
             class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-colors">
            Deudas
          </a>
        </div>

        <!-- Mobile User Menu -->
        <div class="pt-4 pb-3 border-t border-gray-200">
          <div class="px-4">
            <div class="text-base font-medium text-gray-800">{{ $page.props.auth?.user?.name }}</div>
            <div class="text-sm font-medium text-gray-500">{{ $page.props.auth?.user?.email }}</div>
          </div>
          <div class="mt-3 px-2 space-y-1">
            <!-- Mobile Notification Dropdown -->
            <div class="px-3 py-2">
              <NotificationDropdown :notifications="notifications" />
            </div>
            <form method="POST" :action="route('logout')" class="block">
              <button type="submit" 
                      class="block w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 transition-colors">
                Salir
              </button>
            </form>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Header -->
    <header class="bg-white shadow-sm">
      <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <h1 class="text-xl sm:text-2xl font-semibold text-gray-900">Clear Path - Financial Management</h1>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-auto">
      <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-500">© 2025 Clear Path Financial. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import NotificationDropdown from '@/Components/NotificationDropdown.vue';

const mobileMenuOpen = ref(false);
const notifications = ref([]);

// Close mobile menu when clicking outside
const handleClickOutside = (event) => {
  if (mobileMenuOpen.value && !event.target.closest('nav')) {
    mobileMenuOpen.value = false;
  }
};

// Close mobile menu on escape key
const handleEscape = (event) => {
  if (event.key === 'Escape' && mobileMenuOpen.value) {
    mobileMenuOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleEscape);
});
</script>

<style scoped>
/* Custom scrollbar for better mobile experience */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
