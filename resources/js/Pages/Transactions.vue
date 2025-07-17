<template>
  <div class="py-12">
    <div v-if="success" class="mb-6 p-4 bg-green-100 text-green-800 rounded font-semibold text-center">
      {{ success }}
    </div>
    
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <!-- Header with Add Transaction Button -->
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Transacciones</h2>
        <Link :href="route('transactions.create')" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
          Nueva Transacción
        </Link>
      </div>

      <!-- Monthly Summary Card -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">Resumen del Mes</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-green-50 p-4 rounded">
              <div class="text-sm text-green-600 font-medium">Ingresos</div>
              <div class="text-2xl font-bold text-green-700">${{ formatCurrency(monthlySummary.income) }}</div>
            </div>
            <div class="bg-red-50 p-4 rounded">
              <div class="text-sm text-red-600 font-medium">Gastos</div>
              <div class="text-2xl font-bold text-red-700">${{ formatCurrency(monthlySummary.expenses) }}</div>
            </div>
            <div class="p-4 rounded" :class="monthlySummary.net >= 0 ? 'bg-blue-50' : 'bg-orange-50'">
              <div class="text-sm font-medium" :class="monthlySummary.net >= 0 ? 'text-blue-600' : 'text-orange-600'">
                Balance Neto
              </div>
              <div class="text-2xl font-bold" :class="monthlySummary.net >= 0 ? 'text-blue-700' : 'text-orange-700'">
                ${{ formatCurrency(monthlySummary.net) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">Filtros</h3>
          <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
              <select v-model="filterForm.month" class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Todos los meses</option>
                <option v-for="month in months" :key="month.value" :value="month.value">
                  {{ month.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Año</label>
              <select v-model="filterForm.year" class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Todos los años</option>
                <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
              <select v-model="filterForm.type" class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Todos los tipos</option>
                <option value="income">Ingresos</option>
                <option value="expense">Gastos</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
              <select v-model="filterForm.category" class="w-full border border-gray-300 rounded-md px-3 py-2">
                <option value="">Todas las categorías</option>
                <option v-for="category in categories" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
            </div>
          </form>
          <div class="mt-4 flex gap-2">
            <button @click="applyFilters" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded">
              Aplicar Filtros
            </button>
            <button @click="clearFilters" class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
              Limpiar
            </button>
          </div>
        </div>
      </div>

      <!-- Transactions List -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <h3 class="text-lg font-semibold mb-4">Lista de Transacciones</h3>
          
          <div v-if="transactions.data.length === 0" class="text-gray-500 text-center py-8">
            No hay transacciones que coincidan con los filtros aplicados.
          </div>
          
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Fecha
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Tipo
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Categoría
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Monto
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Nota
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Acciones
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="transaction in transactions.data" :key="transaction.id">
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ formatDate(transaction.date) }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full" 
                          :class="transaction.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                      {{ transaction.type === 'income' ? 'Ingreso' : 'Gasto' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ transaction.category }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" 
                      :class="transaction.type === 'income' ? 'text-green-600' : 'text-red-600'">
                    ${{ formatCurrency(transaction.amount) }}
                  </td>
                  <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                    {{ transaction.note || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <Link :href="route('transactions.edit', transaction.id)" 
                          class="text-indigo-600 hover:text-indigo-900 mr-3">
                      Editar
                    </Link>
                    <button @click="deleteTransaction(transaction)" 
                            class="text-red-600 hover:text-red-900">
                      Eliminar
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="transactions.links && transactions.links.length > 3" class="mt-6">
            <nav class="flex justify-center">
              <div class="flex space-x-1">
                <Link v-for="link in transactions.links" 
                      :key="link.label" 
                      :href="link.url"
                      :class="[
                        'px-3 py-2 text-sm font-medium border',
                        link.active 
                          ? 'bg-blue-600 text-white border-blue-600' 
                          : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                      ]"
                      v-html="link.label">
                </Link>
              </div>
            </nav>
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
const transactions = computed(() => page.props.transactions);
const categories = computed(() => page.props.categories);
const monthlySummary = computed(() => page.props.monthlySummary);
const success = computed(() => page.props.success);
const filters = computed(() => page.props.filters || {});

// Filter form
const filterForm = ref({
  month: filters.value.month || '',
  year: filters.value.year || '',
  type: filters.value.type || '',
  category: filters.value.category || ''
});

// Generate months and years for filters
const months = [
  { value: 1, label: 'Enero' },
  { value: 2, label: 'Febrero' },
  { value: 3, label: 'Marzo' },
  { value: 4, label: 'Abril' },
  { value: 5, label: 'Mayo' },
  { value: 6, label: 'Junio' },
  { value: 7, label: 'Julio' },
  { value: 8, label: 'Agosto' },
  { value: 9, label: 'Septiembre' },
  { value: 10, label: 'Octubre' },
  { value: 11, label: 'Noviembre' },
  { value: 12, label: 'Diciembre' }
];

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => currentYear - i);

// Helper functions
function formatCurrency(amount) {
  return Number(amount).toFixed(2);
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('es-ES');
}

function applyFilters() {
  router.get(route('transactions.index'), filterForm.value, {
    preserveState: true,
    preserveScroll: true
  });
}

function clearFilters() {
  filterForm.value = {
    month: '',
    year: '',
    type: '',
    category: ''
  };
  router.get(route('transactions.index'), {}, {
    preserveState: true,
    preserveScroll: true
  });
}

function deleteTransaction(transaction) {
  if (confirm('¿Estás seguro de que quieres eliminar esta transacción?')) {
    router.delete(route('transactions.destroy', transaction.id), {
      preserveState: true,
      preserveScroll: true
    });
  }
}
</script> 