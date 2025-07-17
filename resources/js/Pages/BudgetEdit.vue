<template>
  <div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Editar Presupuesto</h2>
            <Link :href="route('budgets.index')" class="text-gray-600 hover:text-gray-900">
              ← Volver a Presupuestos
            </Link>
          </div>

          <form @submit.prevent="submit" class="space-y-6">
            <!-- Category -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
              <div class="relative">
                <input 
                  v-model="form.category" 
                  type="text" 
                  list="categories"
                  placeholder="Ej: Alimentación, Transporte, Entretenimiento..."
                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required 
                />
                <datalist id="categories">
                  <option v-for="category in categories" :key="category" :value="category">
                    {{ category }}
                  </option>
                </datalist>
              </div>
              <p class="text-sm text-gray-500 mt-1">
                Selecciona una categoría existente o escribe una nueva.
              </p>
              <div v-if="form.errors.category" class="text-red-600 text-sm mt-1">{{ form.errors.category }}</div>
            </div>

            <!-- Budget Limit -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Límite de Presupuesto</label>
              <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">$</span>
                <input 
                  v-model.number="form.limit" 
                  type="number" 
                  step="0.01"
                  min="0.01"
                  placeholder="0.00"
                  class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required 
                />
              </div>
              <p class="text-sm text-gray-500 mt-1">
                Establece el límite máximo que planeas gastar en esta categoría.
              </p>
              <div v-if="form.errors.limit" class="text-red-600 text-sm mt-1">{{ form.errors.limit }}</div>
            </div>

            <!-- Month -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Mes</label>
              <select 
                v-model="form.month" 
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required
              >
                <option value="">Selecciona un mes</option>
                <option v-for="month in availableMonths" :key="month.value" :value="month.value">
                  {{ month.label }}
                </option>
              </select>
              <p class="text-sm text-gray-500 mt-1">
                Selecciona el mes para el cual quieres crear este presupuesto.
              </p>
              <div v-if="form.errors.month" class="text-red-600 text-sm mt-1">{{ form.errors.month }}</div>
            </div>

            <!-- Info Card -->
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
              <div class="flex items-start">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-blue-800">
                    Información sobre Presupuestos
                  </h3>
                  <div class="mt-2 text-sm text-blue-700">
                    <ul class="list-disc list-inside space-y-1">
                      <li>El sistema calculará automáticamente cuánto has gastado basándose en tus transacciones.</li>
                      <li>Recibirás alertas cuando te acerques al límite o lo excedas.</li>
                      <li>Solo puedes tener un presupuesto por categoría por mes.</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
              <Link :href="route('budgets.index')" 
                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
                Cancelar
              </Link>
              <button 
                type="submit" 
                :disabled="form.processing"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded disabled:opacity-50">
                {{ form.processing ? 'Actualizando...' : 'Actualizar Presupuesto' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const budget = computed(() => page.props.budget);
const categories = computed(() => page.props.categories || []);

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

// Format budget month for form
const budgetMonth = computed(() => {
  const date = new Date(budget.value.month);
  return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
});

const form = useForm({
  category: budget.value.category,
  limit: budget.value.limit,
  month: budgetMonth.value
});

function submit() {
  form.put(route('budgets.update', budget.value.id));
}
</script> 