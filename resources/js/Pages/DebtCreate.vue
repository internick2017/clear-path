<template>
  <div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Nueva Deuda</h2>
            <Link :href="route('debts.index')" class="text-gray-600 hover:text-gray-900">
              ← Volver a Deudas
            </Link>
          </div>

          <form @submit.prevent="submit" class="space-y-6">
            <!-- Debt Name -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de la Deuda</label>
              <input 
                v-model="form.name" 
                type="text" 
                placeholder="Ej: Tarjeta de Crédito Visa, Préstamo Personal, etc."
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required 
              />
              <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
            </div>

            <!-- Currency -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Moneda</label>
              <select 
                v-model="form.currency"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required>
                <option v-for="currency in currencies" :key="currency.code" :value="currency.code">
                  {{ currency.display }}
                </option>
              </select>
              <div v-if="form.errors.currency" class="text-red-600 text-sm mt-1">{{ form.errors.currency }}</div>
            </div>

            <!-- Flexible Debt System - 4 Fields (Minimum 3 Required) -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
              <h3 class="text-lg font-semibold text-yellow-800 mb-2">📊 Sistema Flexible de Deudas</h3>
              <p class="text-sm text-yellow-700 mb-3">
                Llena <strong>mínimo 3 de los 4 campos</strong> siguientes. El campo faltante se calculará automáticamente.
              </p>
              <div class="text-xs text-yellow-600">
                <strong>Campos disponibles:</strong> Capital Original | Monto Total | Tasa de Interés | Pago Mensual
              </div>
            </div>

            <!-- Flexible Fields Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- 1. Original Amount (Capital) -->
              <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  💰 Capital Original 
                  <span class="text-xs text-gray-500">(Opcional)</span>
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-2 text-gray-500">{{ getCurrencySymbol(form.currency) }}</span>
                  <input 
                    v-model.number="form.original_amount" 
                    type="number" 
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    class="w-full border rounded-md pl-8 pr-3 py-2 focus:ring-2 focus:ring-blue-500"
                    :class="form.original_amount ? 'border-green-300 bg-green-50' : 'border-gray-300'"
                    @input="calculateMissingField"
                  />
                </div>
                <div class="text-xs text-gray-600 mt-1">Monto que pediste prestado inicialmente</div>
                <div v-if="form.errors.original_amount" class="text-red-600 text-sm mt-1">{{ form.errors.original_amount }}</div>
              </div>

              <!-- 2. Total Amount -->
              <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  📈 Monto Total a Pagar 
                  <span class="text-xs text-gray-500">(Opcional)</span>
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-2 text-gray-500">{{ getCurrencySymbol(form.currency) }}</span>
                  <input 
                    v-model.number="form.total_amount" 
                    type="number" 
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    class="w-full border rounded-md pl-8 pr-3 py-2 focus:ring-2 focus:ring-blue-500"
                    :class="form.total_amount ? 'border-green-300 bg-green-50' : 'border-gray-300'"
                    @input="calculateMissingField"
                  />
                </div>
                <div class="text-xs text-gray-600 mt-1">Total final con intereses incluidos</div>
                <div v-if="form.errors.total_amount" class="text-red-600 text-sm mt-1">{{ form.errors.total_amount }}</div>
              </div>

              <!-- 3. Interest Rate -->
              <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  📊 Tasa de Interés 
                  <span class="text-xs text-gray-500">(Opcional)</span>
                </label>
                <div class="relative">
                  <input 
                    v-model.number="form.interest_rate" 
                    type="number" 
                    step="0.01"
                    min="0"
                    max="100"
                    placeholder="0.00"
                    class="w-full border rounded-md pr-10 pl-3 py-2 focus:ring-2 focus:ring-blue-500"
                    :class="form.interest_rate ? 'border-green-300 bg-green-50' : 'border-gray-300'"
                    @input="calculateMissingField"
                  />
                  <span class="absolute right-3 top-2 text-gray-500">% anual</span>
                </div>
                <div class="text-xs text-gray-600 mt-1">Ejemplo: 18.5 para 18.5% anual</div>
                <div v-if="form.errors.interest_rate" class="text-red-600 text-sm mt-1">{{ form.errors.interest_rate }}</div>
              </div>

              <!-- 4. Monthly Payment -->
              <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  💳 Pago Mensual 
                  <span class="text-xs text-gray-500">(Opcional)</span>
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-2 text-gray-500">{{ getCurrencySymbol(form.currency) }}</span>
                  <input 
                    v-model.number="form.minimum_payment" 
                    type="number" 
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    class="w-full border rounded-md pl-8 pr-3 py-2 focus:ring-2 focus:ring-blue-500"
                    :class="form.minimum_payment ? 'border-green-300 bg-green-50' : 'border-gray-300'"
                    @input="calculateMissingField"
                  />
                </div>
                <div class="text-xs text-gray-600 mt-1">Pago mínimo o fijo mensual</div>
                <div v-if="form.errors.minimum_payment" class="text-red-600 text-sm mt-1">{{ form.errors.minimum_payment }}</div>
              </div>
            </div>

            <!-- Calculation Status -->
            <div v-if="fieldsProvided >= 3" class="bg-green-50 border border-green-200 rounded-lg p-4">
              <div class="flex items-center mb-2">
                <svg class="h-5 w-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-green-800">✅ Sistema listo - {{ fieldsProvided }} campos completados</span>
              </div>
              <div v-if="calculatedField" class="text-xs text-green-700">
                📈 <strong>Campo calculado:</strong> {{ calculatedField }}
              </div>
            </div>
            
            <div v-else class="bg-red-50 border border-red-200 rounded-lg p-4">
              <div class="flex items-center mb-2">
                <svg class="h-5 w-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm font-medium text-red-800">⚠️ Faltan campos - {{ fieldsProvided }} de 3 mínimos</span>
              </div>
              <div class="text-xs text-red-700">
                Completa al menos 3 campos para continuar.
              </div>
            </div>

            <!-- Amount Already Paid -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                💸 Ya Pagado Anteriormente 
                <span class="text-xs text-gray-500">(Opcional)</span>
              </label>
              <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">{{ getCurrencySymbol(form.currency) }}</span>
                <input 
                  v-model.number="form.amount_paid" 
                  type="number" 
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <div class="text-sm text-gray-600 mt-1">¿Cuánto has pagado antes de registrar esta deuda? (Para personas que ya llevan tiempo pagando)</div>
              <div v-if="form.errors.amount_paid" class="text-red-600 text-sm mt-1">{{ form.errors.amount_paid }}</div>
            </div>


            <!-- Due Date -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Vencimiento</label>
              <input 
                v-model="form.due_date" 
                type="date"
                :min="today"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required 
              />
              <div class="text-sm text-gray-600 mt-1">Fecha límite para el próximo pago</div>
              <div v-if="form.errors.due_date" class="text-red-600 text-sm mt-1">{{ form.errors.due_date }}</div>
            </div>

            <!-- Note -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Nota (Opcional)</label>
              <textarea 
                v-model="form.note" 
                rows="3"
                placeholder="Detalles adicionales sobre esta deuda..."
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              ></textarea>
              <div v-if="form.errors.note" class="text-red-600 text-sm mt-1">{{ form.errors.note }}</div>
            </div>

            <!-- Examples and Tips -->
            <div class="bg-blue-50 p-4 rounded-md">
              <h4 class="text-sm font-medium text-blue-900 mb-2">💡 Ejemplos de Uso:</h4>
              <div class="text-sm text-blue-700 space-y-2">
                <div><strong>Escenario A:</strong> Tienes Capital (R$10.000) + Tasa (18%) + Pago Mensual (R$500) → Sistema calcula Monto Total</div>
                <div><strong>Escenario B:</strong> Tienes Monto Total (R$15.000) + Pago Mensual (R$600) + Tasa (20%) → Sistema calcula Capital Original</div>
                <div><strong>Escenario C:</strong> Solo tienes Monto Total + Capital + Pago Mensual → Sistema calcula la Tasa</div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
              <Link :href="route('debts.index')" 
                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
                Cancelar
              </Link>
              <button 
                type="submit" 
                :disabled="form.processing"
                class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded disabled:opacity-50">
                {{ form.processing ? 'Creando...' : 'Registrar Deuda' }}
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
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useCurrency } from '@/composables/useCurrency.js';
import { computed, ref } from 'vue';

defineOptions({ layout: AppLayout });

// Set default date to today
const today = new Date().toISOString().split('T')[0];

// Currency support
const { getSupportedCurrencies, getCurrencySymbol } = useCurrency();
const currencies = getSupportedCurrencies();

const form = useForm({
  name: '',
  original_amount: '',
  total_amount: '',
  amount_paid: 0,
  currency: 'BRL', // Default to Brazilian Real
  interest_rate: '',
  minimum_payment: '',
  due_date: '',
  note: ''
});

// Calculation state
const calculatedField = ref('');

// Count fields provided
const fieldsProvided = computed(() => {
  let count = 0;
  if (form.original_amount && form.original_amount > 0) count++;
  if (form.total_amount && form.total_amount > 0) count++;
  if (form.interest_rate && form.interest_rate > 0) count++;
  if (form.minimum_payment && form.minimum_payment > 0) count++;
  return count;
});

// Calculate missing field
function calculateMissingField() {
  if (fieldsProvided.value < 3) {
    calculatedField.value = '';
    return;
  }

  // Simple calculation logic (can be enhanced)
  if (!form.original_amount && form.total_amount && form.interest_rate) {
    // Calculate original amount
    const interestMultiplier = 1 + (form.interest_rate / 100);
    form.original_amount = (form.total_amount / interestMultiplier).toFixed(2);
    calculatedField.value = `Capital Original: ${getCurrencySymbol(form.currency)}${form.original_amount}`;
  }
  
  else if (!form.total_amount && form.original_amount && form.interest_rate) {
    // Calculate total amount
    const interestMultiplier = 1 + (form.interest_rate / 100);
    form.total_amount = (form.original_amount * interestMultiplier).toFixed(2);
    calculatedField.value = `Monto Total: ${getCurrencySymbol(form.currency)}${form.total_amount}`;
  }
  
  else if (!form.interest_rate && form.original_amount && form.total_amount && form.original_amount > 0) {
    // Calculate interest rate
    form.interest_rate = (((form.total_amount / form.original_amount) - 1) * 100).toFixed(2);
    calculatedField.value = `Tasa de Interés: ${form.interest_rate}% anual`;
  }
  
  else if (!form.minimum_payment && form.total_amount) {
    // Estimate minimum payment (24 months default)
    form.minimum_payment = (form.total_amount / 24).toFixed(2);
    calculatedField.value = `Pago Mensual Estimado: ${getCurrencySymbol(form.currency)}${form.minimum_payment}`;
  }
}

function submit() {
  // Validation
  if (fieldsProvided.value < 3) {
    alert('Por favor completa al menos 3 de los 4 campos principales.');
    return;
  }
  
  // Set the amount field based on what we have
  if (form.total_amount) {
    form.amount = form.total_amount;
  } else if (form.original_amount) {
    form.amount = form.original_amount;
  }
  
  form.post(route('debts.store'));
}
</script>