<template>
  <nav class="bg-white shadow mb-6">
    <div class="max-w-7xl mx-auto px-4 py-3">
      <div class="flex justify-between items-center">
        <!-- Left side navigation -->
        <div class="flex gap-6 items-center">
          <Link href="/dashboard" class="font-semibold text-gray-700 hover:text-blue-600">Dashboard</Link>
          <Link href="/transactions" class="font-semibold text-gray-700 hover:text-blue-600">Transacciones</Link>
          <Link href="/budgets" class="font-semibold text-gray-700 hover:text-blue-600">Presupuestos</Link>
          <Link href="/goals" class="font-semibold text-gray-700 hover:text-blue-600">Metas</Link>
          <Link href="/debts" class="font-semibold text-gray-700 hover:text-blue-600">Deudas</Link>
        </div>
        
        <!-- Right side navigation -->
        <div class="flex items-center gap-4">
          <!-- Notification Dropdown -->
          <NotificationDropdown :notifications="notifications" />
          
          <!-- User Menu -->
          <div class="flex items-center gap-4">
            <Link href="/profile" class="font-semibold text-gray-700 hover:text-blue-600">Profile</Link>
            <form method="POST" action="/logout" class="inline">
              <input type="hidden" name="_token" :value="csrf" />
              <button type="submit" class="font-semibold text-red-600 hover:text-red-800">Logout</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { usePage, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import NotificationDropdown from './NotificationDropdown.vue';

const csrf = usePage().props.csrf_token ?? '';
const notifications = ref([]);

// Load notifications on component mount
onMounted(async () => {
  try {
    const response = await fetch(route('notifications.recent'));
    if (response.ok) {
      notifications.value = await response.json();
    }
  } catch (error) {
    console.error('Error loading notifications:', error);
  }
});

// Listen for notification updates (you can implement this with WebSockets or polling)
const refreshNotifications = async () => {
  try {
    const response = await fetch(route('notifications.recent'));
    if (response.ok) {
      notifications.value = await response.json();
    }
  } catch (error) {
    console.error('Error refreshing notifications:', error);
  }
};

// Refresh notifications every 30 seconds
setInterval(refreshNotifications, 30000);
</script>
