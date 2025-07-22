<template>
  <div class="py-12">
    <div v-if="success" class="mb-6 p-4 bg-green-100 text-green-800 rounded font-semibold text-center">
      {{ success }}
    </div>
    
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <!-- Header with Add Budget Button -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Presupuestos</h2>
        <Link :href="route('budgets.create')" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
          Nuevo Presupuesto
        </Link>
      </div>

      <!-- Month Filter -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
          <div class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Mes:</label>
            <select v-model="selectedMonth" @change="changeMonth" class="border border-gray-300 rounded-md px-3 py-2">
              <option v-for="month in availableMonths" :key="month.value" :value="month.value">
                {{ month.label }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Budget Overview -->
      <div v-if="budgets.length === 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6 text-center text-gray-500">
          No hay presupuestos configurados para {{ formatMonth(currentMonth) }}.
          <Link :href="route('budgets.create')" class="text-blue-600 hover:text-blue-800 ml-2">
            Crear el primer presupuesto
          </Link>
        </div>
      </div>

      <!-- Budget Cards -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <div v-for="budget in budgets" :key="budget.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-between items-start mb-4">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ budget.category }}</h3>
                <p class="text-sm text-gray-500">{{ formatMonth(currentMonth) }}</p>
              </div>
              <div class="flex gap-2">
                <Link :href="route('budgets.edit', budget.id)" 
                      class="text-indigo-600 hover:text-indigo-900 text-sm">
                  Editar
                </Link>
                <button @click="deleteBudget(budget)" 
                        class="text-red-600 hover:text-red-900 text-sm">
                  Eliminar
                </button>
              </div>
            </div>

            <!-- Budget Progress -->
            <div class="mb-4">
              <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">Progreso</span>
                <span class="text-sm font-medium" :class="budget.is_exceeded ? 'text-red-600' : 'text-gray-700'">
                  {{ Math.round(budget.percentage) }}%
                </span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-3">
                <div 
                  :class="budget.is_exceeded ? 'bg-red-500' : budget.percentage > 80 ? 'bg-yellow-500' : 'bg-green-500'"
                  class="h-3 rounded-full transition-all duration-300"
                  :style="{ width: Math.min(budget.percentage, 100) + '%' }"
                ></div>
              </div>
            </div>

            <!-- Budget Details -->
            <div class="space-y-2 text-sm">
              <div class="flex justify-between">
                <span class="text-gray-600">Limit:</span>
                <span class="font-semibold">${{ formatCurrency(budget.limit) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Gastado:</span>
                <span class="font-semibold" :class="budget.is_exceeded ? 'text-red-600' : 'text-gray-900'">
                  ${{ formatCurrency(budget.actual_spent) }}
                </span>
              </div>
              <div class="flex justify-between border-t pt-2">
                <span class="text-gray-600">Restante:</span>
                <span class="font-semibold" :class="budget.remaining < 0 ? 'text-red-600' : 'text-green-600'">
                  ${{ formatCurrency(budget.remaining) }}
                </span>
              </div>
            </div>

            <!-- Exceeded Alert -->
            <div v-if="budget.is_exceeded" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-red-800">
                    Budget exceeded by ${{ formatCurrency(budget.actual_spent - budget.limit) }}!
                  </p>
                </div>
              </div>
            </div>

            <!-- Warning Alert -->
            <div v-else-if="budget.percentage > 80" class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-yellow-800">
                    Near limit. ${{ formatCurrency(budget.remaining) }} remaining.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary Table -->
      <div v-if="budgets.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">Resumen de Presupuestos</h3>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Category
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Limit
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Gastado
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Restante
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Estado
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="budget in budgets" :key="budget.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ budget.category }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${{ formatCurrency(budget.limit) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm" :class="budget.is_exceeded ? 'text-red-600' : 'text-gray-900'">
                    ${{ formatCurrency(budget.actual_spent) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm" :class="budget.remaining < 0 ? 'text-red-600' : 'text-green-600'">
                    ${{ formatCurrency(budget.remaining) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                          :class="budget.is_exceeded ? 'bg-red-100 text-red-800' : 
                                  budget.percentage > 80 ? 'bg-yellow-100 text-yellow-800' : 
                                  'bg-green-100 text-green-800'">
                      {{ budget.is_exceeded ? 'Excedido' : budget.percentage > 80 ? 'Advertencia' : 'Normal' }}
                    </span>
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
import { ref, computed } from 'vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const budgets = computed(() => page.props.budgets || []);
const currentMonth = computed(() => page.props.currentMonth);
const success = computed(() => page.props.success);

const selectedMonth = ref(currentMonth.value);

// Generate available months (current month and next 11 months)
const availableMonths = computed(() => {
  const months = [];
  const now = new Date();
  
  for (let i = 0; i < 12; i++) {
    const date = new Date(now.getFullYear(), now.getMonth() + i, 1);
    const value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
    const label = date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
    months.push({ value, label });
  }
  
  return months;
});

// Helper functions
function formatCurrency(amount) {
  return Number(amount).toFixed(2);
}

function formatMonth(monthString) {
  const date = new Date(monthString + '-01');
  return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'long' });
}

function changeMonth() {
  router.get(route('budgets.index'), { month: selectedMonth.value }, {
    preserveState: true,
    preserveScroll: true
  });
}

function deleteBudget(budget) {
        if (confirm('Are you sure you want to delete this budget?')) {
    router.delete(route('budgets.destroy', budget.id), {
      preserveState: true,
      preserveScroll: true
    });
  }
}
</script> 