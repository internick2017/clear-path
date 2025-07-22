<template>
  <div class="relative">
    <!-- Notification Bell Button -->
    <button @click="toggleDropdown" class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      <!-- Notification Badge -->
      <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown Menu -->
    <div v-if="isOpen" class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
      <!-- Header -->
      <div class="px-4 py-3 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-gray-900">Notificaciones</h3>
        <button v-if="unreadCount > 0" @click="markAllAsRead" class="text-sm text-blue-600 hover:text-blue-800">
          Mark all as read
        </button>
      </div>

      <!-- Notifications List -->
      <div class="max-h-96 overflow-y-auto">
        <div v-if="notifications.length === 0" class="px-4 py-8 text-center text-gray-500">
          No hay notificaciones
        </div>
        
        <div v-for="notification in notifications" :key="notification.id" 
             :class="['px-4 py-3 border-b border-gray-100 hover:bg-gray-50 cursor-pointer transition-colors', 
                      !notification.read_at ? 'bg-blue-50' : '']"
             @click="markAsRead(notification)">
          
          <!-- Budget Exceeded Notification -->
          <div v-if="notification.type === 'App\\Notifications\\BudgetExceededNotification'" class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Presupuesto excedido</p>
              <p class="text-sm text-gray-600">{{ notification.data.message }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDate(notification.created_at) }}</p>
            </div>
          </div>

          <!-- Goal Reached Notification -->
          <div v-else-if="notification.type === 'App\\Notifications\\GoalReachedNotification'" class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Goal reached!</p>
              <p class="text-sm text-gray-600">{{ notification.data.message }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDate(notification.created_at) }}</p>
            </div>
          </div>

          <!-- Debt Payment Reminder Notification -->
          <div v-else-if="notification.type === 'App\\Notifications\\DebtPaymentReminderNotification'" class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Recordatorio de pago</p>
              <p class="text-sm text-gray-600">{{ notification.data.message }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDate(notification.created_at) }}</p>
            </div>
          </div>

          <!-- Transaction Category Change Notification -->
          <div v-else-if="notification.type === 'App\\Notifications\\TransactionCategoryChangeNotification'" class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 110 2h-1v12a3 3 0 01-3 3H6a3 3 0 01-3-3V6H2a1 1 0 110-2h4z" />
                </svg>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Category changed</p>
              <p class="text-sm text-gray-600">{{ notification.data.message }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDate(notification.created_at) }}</p>
            </div>
          </div>

          <!-- Generic Notification -->
          <div v-else class="flex items-start space-x-3">
            <div class="flex-shrink-0">
              <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900">Notificación</p>
              <p class="text-sm text-gray-600">{{ notification.data.message || 'New notification' }}</p>
              <p class="text-xs text-gray-500 mt-1">{{ formatDate(notification.created_at) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div v-if="notifications.length > 0" class="px-4 py-3 border-t border-gray-200">
        <button @click="viewAllNotifications" class="text-sm text-blue-600 hover:text-blue-800">
          Ver todas las notificaciones
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

// Props
const props = defineProps({
  notifications: {
    type: Array,
    default: () => []
  }
});

// Reactive state
const isOpen = ref(false);

// Computed properties
const unreadCount = computed(() => {
  return props.notifications.filter(n => !n.read_at).length;
});

// Methods
const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const markAsRead = (notification) => {
  if (!notification.read_at) {
    router.post(route('notifications.markAsRead', notification.id), {}, {
      preserveState: true,
      preserveScroll: true
    });
  }
};

const markAllAsRead = () => {
  router.post(route('notifications.markAllAsRead'), {}, {
    preserveState: true,
    preserveScroll: true
  });
};

const viewAllNotifications = () => {
  router.get(route('notifications.index'));
  isOpen.value = false;
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Close dropdown when clicking outside
const handleClickOutside = (event) => {
  if (isOpen.value && !event.target.closest('.relative')) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script> 