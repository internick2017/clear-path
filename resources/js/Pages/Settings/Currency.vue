<template>
  <div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Configuración de Moneda</h2>
            <Link :href="route('dashboard')" class="text-gray-600 hover:text-gray-900">
              ← Volver al Dashboard
            </Link>
          </div>

          <!-- Success Message -->
          <div v-if="success" class="mb-6 p-4 bg-green-100 text-green-800 rounded font-semibold">
            {{ success }}
          </div>

          <!-- Current Currency Display -->
          <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                  Moneda de Visualización Actual
                </h3>
                <div class="mt-1 text-sm text-blue-700">
                  <span class="font-medium">{{ currentCurrency?.display || 'USD - US Dollar ($)' }}</span>
                </div>
                <p class="mt-1 text-xs text-blue-600">
                  Todos los montos se muestran en esta moneda, pero puedes registrar transacciones en cualquier moneda.
                </p>
              </div>
            </div>
          </div>

          <form @submit.prevent="updateCurrency" class="space-y-6">
            <!-- Currency Selection -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Selecciona tu Moneda de Visualización Preferida
              </label>
              <select 
                v-model="form.display_currency"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required>
                <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                  {{ currency.display }}
                </option>
              </select>
              <div v-if="form.errors.display_currency" class="text-red-600 text-sm mt-1">
                {{ form.errors.display_currency }}
              </div>
              <div class="text-sm text-gray-600 mt-2">
                Esta configuración afecta cómo se muestran los montos en toda la aplicación, pero no cambia los datos guardados.
              </div>
            </div>

            <!-- Preview -->
            <div v-if="form.display_currency" class="bg-gray-50 border border-gray-200 rounded-md p-4">
              <h4 class="text-sm font-medium text-gray-800 mb-2">Vista Previa:</h4>
              <div class="space-y-1 text-sm text-gray-700">
                <div>Ejemplo de monto: {{ formatCurrency(1234.56, form.display_currency) }}</div>
                <div class="text-xs text-gray-500">
                  <span v-if="isCommaDecimalCurrency(form.display_currency)">
                    Esta moneda usa formato europeo/brasileño: punto (.) para miles, coma (,) para decimales
                  </span>
                  <span v-else>
                    Esta moneda usa formato americano: coma (,) para miles, punto (.) para decimales
                  </span>
                </div>
              </div>
            </div>

            <!-- Information Box -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-yellow-800">
                    Información Importante
                  </h3>
                  <div class="mt-1 text-sm text-yellow-700">
                    <ul class="list-disc list-inside space-y-1">
                      <li>Puedes registrar transacciones y deudas en cualquier moneda soportada</li>
                      <li>El sistema convierte automáticamente para mostrar todo en tu moneda preferida</li>
                      <li>Los datos originales se mantienen seguros en la base de datos</li>
                      <li>Cambiar esta configuración no afecta transacciones existentes</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
              <button 
                type="submit" 
                :disabled="form.processing"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded disabled:opacity-50">
                {{ form.processing ? 'Guardando...' : 'Guardar Configuración' }}
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
import { useCurrency } from '@/composables/useCurrency.js';

defineOptions({ layout: AppLayout });

const page = usePage();
const success = computed(() => page.props.success);

// Currency support
const { getSupportedCurrencies, formatCurrency, getDecimalSeparator } = useCurrency();
const currencies = getSupportedCurrencies();

// Get current user currency
const currentUserCurrency = computed(() => page.props.auth.user.display_currency || 'USD');
const currentCurrency = computed(() => {
  return currencies.find(c => c.code === currentUserCurrency.value);
});

const form = useForm({
  display_currency: currentUserCurrency.value
});

function updateCurrency() {
  form.patch(route('settings.currency.update'));
}

function isCommaDecimalCurrency(currency) {
  return ['BRL', 'EUR', 'ARS', 'COP'].includes(currency);
}
</script>