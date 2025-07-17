<template>
  <div class="py-12">
    <div v-if="success" class="mb-6 p-4 bg-green-100 text-green-800 rounded font-semibold text-center">
      {{ success }}
    </div>
    
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Gestión de Deudas</h2>
        <Link :href="route('debts.plan')" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
          Ver Plan de Pago
        </Link>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Deudas Activas</p>
                <p class="text-2xl font-bold text-gray-900">{{ activeDebts.length }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Deuda Total</p>
                <p class="text-2xl font-bold text-red-600">${{ formatCurrency(totalActiveDebt) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">Deudas Pagadas</p>
                <p class="text-2xl font-bold text-green-600">{{ paidDebts.length }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Active Debts -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">Deudas Activas</h3>
          
          <div v-if="activeDebts.length === 0" class="text-gray-500 text-center py-8">
            No tienes deudas activas. ¡Felicitaciones! 🎉
          </div>
          
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Deuda
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Monto
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Tasa de Interés
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Pago Mínimo
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Fecha Vencimiento
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="debt in activeDebts" :key="debt.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ debt.name }}</div>
                    <div class="text-sm text-gray-500">{{ debt.strategy }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${{ formatCurrency(debt.amount) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ debt.interest_rate }}%
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${{ formatCurrency(debt.minimum_payment) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(debt.due_date) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button @click="markAsPaid(debt)" 
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                      Marcar como Pagada
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Paid Debts -->
      <div v-if="paidDebts.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">Deudas Pagadas</h3>
          
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Deuda
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Monto
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Fecha de Pago
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="debt in paidDebts" :key="debt.id">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ debt.name }}</div>
                    <div class="text-sm text-gray-500">{{ debt.strategy }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${{ formatCurrency(debt.amount) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(debt.paid_at) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button @click="markAsActive(debt)" 
                            class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm">
                      Marcar como Activa
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const activeDebts = computed(() => page.props.activeDebts || []);
const paidDebts = computed(() => page.props.paidDebts || []);
const success = computed(() => page.props.success);

// Calculate total active debt
const totalActiveDebt = computed(() => {
  return activeDebts.value.reduce((total, debt) => total + parseFloat(debt.amount), 0);
});

// Helper functions
function formatCurrency(amount) {
  return Number(amount).toFixed(2);
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('es-ES');
}

function markAsPaid(debt) {
  if (confirm(`¿Estás seguro de que quieres marcar "${debt.name}" como pagada?`)) {
    router.post(route('debts.markAsPaid', debt.id), {}, {
      preserveState: true,
      preserveScroll: true
    });
  }
}

function markAsActive(debt) {
  if (confirm(`¿Estás seguro de que quieres marcar "${debt.name}" como activa nuevamente?`)) {
    router.post(route('debts.markAsActive', debt.id), {}, {
      preserveState: true,
      preserveScroll: true
    });
  }
}
</script> 