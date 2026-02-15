<template>
  <div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Editar Deuda</h2>
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

            <!-- Flexible Debt System - Manual Calculation -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
              <h3 class="text-lg font-semibold text-yellow-800 mb-2">📊 Sistema de Cálculo de Deudas</h3>
              <p class="text-sm text-yellow-700 mb-3">
                Llena los campos que conoces y usa el botón <strong>"Calcular"</strong> para obtener el campo faltante.
              </p>
              <div class="text-xs text-yellow-600 mb-2">
                <strong>Campos disponibles:</strong> Capital Original | Monto Total | Tasa de Interés | Pago Mensual
              </div>
              <!-- Format Instructions -->
              <div class="text-xs text-yellow-600 bg-yellow-100 p-2 rounded border">
                <strong>💡 Formato brasileiro:</strong> Usa comas para decimales (ej: 1.500,75) y puntos para miles (ej: 1.500,75)
              </div>
            </div>

            <!-- 1. Original Amount -->
            <div class="relative">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                💰 Capital Original
                <span class="text-xs text-gray-500">(Opcional)</span>
              </label>
              <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">{{ getCurrencySymbol(form.currency) }}</span>
                <input
                  v-model="form.original_amount"
                  type="text"
                  placeholder="1.500,75"
                  class="w-full border rounded-md pl-8 pr-10 py-2 focus:ring-2 focus:ring-blue-500"
                  :class="form.original_amount ? 'border-green-300 bg-green-50' : 'border-gray-300'"
                />
                <button
                  v-if="form.original_amount"
                  type="button"
                  @click="clearField('original_amount')"
                  class="absolute right-2 top-2 text-gray-400 hover:text-red-500 text-sm"
                  title="Limpiar campo"
                >
                  ✕
                </button>
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
                  v-model="form.total_amount"
                  type="text"
                  placeholder="4.536,00"
                  class="w-full border rounded-md pl-8 pr-10 py-2 focus:ring-2 focus:ring-blue-500"
                  :class="form.total_amount ? 'border-green-300 bg-green-50' : 'border-gray-300'"
                />
                <button
                  v-if="form.total_amount"
                  type="button"
                  @click="clearField('total_amount')"
                  class="absolute right-2 top-2 text-gray-400 hover:text-red-500 text-sm"
                  title="Limpiar campo"
                >
                  ✕
                </button>
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
                  v-model="form.interest_rate"
                  type="text"
                  placeholder="73,66"
                  class="w-full border rounded-md pl-3 pr-10 py-2 focus:ring-2 focus:ring-blue-500"
                  :class="form.interest_rate ? 'border-green-300 bg-green-50' : 'border-gray-300'"
                />
                <span class="absolute right-8 top-2 text-gray-500">% anual</span>
                <button
                  v-if="form.interest_rate"
                  type="button"
                  @click="clearField('interest_rate')"
                  class="absolute right-2 top-2 text-gray-400 hover:text-red-500 text-sm"
                  title="Limpiar campo"
                >
                  ✕
                </button>
              </div>
              <div class="text-xs text-gray-600 mt-1">Exemplo: 18,5 para 18,5% anual</div>
              <div v-if="form.errors.interest_rate" class="text-red-600 text-sm mt-1">{{ form.errors.interest_rate }}</div>
            </div>

            <!-- 4. Minimum Payment -->
            <div class="relative">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                💳 Pago Mensual
                <span class="text-xs text-gray-500">(Opcional)</span>
              </label>
              <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">{{ getCurrencySymbol(form.currency) }}</span>
                <input
                  v-model="form.minimum_payment"
                  type="text"
                  placeholder="252,00"
                  class="w-full border rounded-md pl-8 pr-10 py-2 focus:ring-2 focus:ring-blue-500"
                  :class="form.minimum_payment ? 'border-green-300 bg-green-50' : 'border-gray-300'"
                />
                <button
                  v-if="form.minimum_payment"
                  type="button"
                  @click="clearField('minimum_payment')"
                  class="absolute right-2 top-2 text-gray-400 hover:text-red-500 text-sm"
                  title="Limpiar campo"
                >
                  ✕
                </button>
              </div>
              <div class="text-xs text-gray-600 mt-1">Pago mínimo o fijo mensual</div>
              <div v-if="form.errors.minimum_payment" class="text-red-600 text-sm mt-1">{{ form.errors.minimum_payment }}</div>
            </div>

            <!-- Calculate Button -->
            <div class="text-center">
              <button
                type="button"
                @click="calculateMissingField"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow-md transition-colors flex items-center"
              >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Calcular Campo Faltante
              </button>
            </div>

            <!-- Status Message -->
            <div v-if="fieldsProvided < 3" class="text-center mt-2">
              <p class="text-sm text-gray-600">
                Completa al menos 3 campos para calcular el faltante
              </p>
            </div>

            <!-- Calculation Result -->
            <div v-if="calculatedField" class="mt-4 p-4 rounded-lg border" :class="calculatedField.includes('⚠️') ? 'bg-red-50 border-red-200' : 'bg-green-50 border-green-200'">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <span v-if="calculatedField.includes('⚠️')" class="text-red-600 mr-2">⚠️</span>
                  <span v-else class="text-green-600 mr-2">📈</span>
                  <span class="text-sm font-medium" :class="calculatedField.includes('⚠️') ? 'text-red-800' : 'text-green-800'">
                    {{ calculatedField.includes('⚠️') ? 'Validación:' : 'Resultado:' }}
                  </span>
                </div>
                <button
                  type="button"
                  @click="clearCalculatedFields"
                  class="text-xs bg-yellow-100 hover:bg-yellow-200 text-yellow-800 px-2 py-1 rounded border border-yellow-300 transition-colors"
                >
                  🔄 Limpiar
                </button>
              </div>
              <div class="text-sm mt-2" :class="calculatedField.includes('⚠️') ? 'text-red-700' : 'text-green-700'">
                {{ calculatedField }}
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
                placeholder="Información adicional sobre esta deuda..."
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              ></textarea>
              <div v-if="form.errors.note" class="text-red-600 text-sm mt-1">{{ form.errors.note }}</div>
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
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded disabled:opacity-50">
                {{ form.processing ? 'Actualizando...' : 'Actualizar Deuda' }}
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
import { useCurrency } from '@/composables/useCurrency.js';
import { computed, ref } from 'vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const debt = computed(() => page.props.debt);

// Set default date to today
const today = new Date().toISOString().split('T')[0];

// Currency support
const { getSupportedCurrencies, getCurrencySymbol } = useCurrency();
const currencies = getSupportedCurrencies();

const form = useForm({
  name: debt.value.name,
  original_amount: debt.value.original_amount || '',
  total_amount: debt.value.total_amount || '',
  amount: debt.value.amount || '', // This field will be set based on total_amount or original_amount
  amount_paid: debt.value.amount_paid || 0,
  currency: debt.value.currency || 'BRL', // Default to Brazilian Real
  interest_rate: debt.value.interest_rate || '',
  minimum_payment: debt.value.minimum_payment || '',
  due_date: debt.value.due_date ? new Date(debt.value.due_date).toISOString().split('T')[0] : today,
  note: debt.value.note || ''
});

// Calculation state
const calculatedField = ref('');
const showDebug = ref(false);

// Helper function to parse numbers safely (handles Brazilian format)
const parseNumber = (value) => {
  if (!value || value === '') return 0;

  // Convert Brazilian format (1.500,75) to US format (1500.75)
  let cleanValue = String(value).trim();

  // If it contains both comma and dot, assume Brazilian format
  if (cleanValue.includes(',') && cleanValue.includes('.')) {
    // Remove dots (thousands separator) and replace comma with dot (decimal separator)
    cleanValue = cleanValue.replace(/\./g, '').replace(',', '.');
  }
  // If it only contains comma, treat it as decimal separator
  else if (cleanValue.includes(',') && !cleanValue.includes('.')) {
    cleanValue = cleanValue.replace(',', '.');
  }

  return parseFloat(cleanValue) || 0;
};

// Count fields provided
const fieldsProvided = computed(() => {
  let count = 0;
  if (form.original_amount && form.original_amount.trim() !== '' && parseNumber(form.original_amount) > 0) count++;
  if (form.total_amount && form.total_amount.trim() !== '' && parseNumber(form.total_amount) > 0) count++;
  if (form.interest_rate && form.interest_rate.trim() !== '' && parseNumber(form.interest_rate) > 0) count++;
  if (form.minimum_payment && form.minimum_payment.trim() !== '' && parseNumber(form.minimum_payment) > 0) count++;
  return count;
});

// Calculate missing field
function calculateMissingField() {
  if (fieldsProvided.value < 3) {
    calculatedField.value = '⚠️ Error: Necesitas completar al menos 3 campos para calcular el faltante';
    return;
  }

  // Get parsed values
  const originalAmount = parseNumber(form.original_amount);
  const totalAmount = parseNumber(form.total_amount);
  const interestRate = parseNumber(form.interest_rate);
  const minimumPayment = parseNumber(form.minimum_payment);

  // Calculate missing field with proper decimal handling and validation
  if (!form.original_amount && totalAmount > 0 && interestRate > 0) {
    // Calculate original amount: original = total / (1 + rate/100)
    const interestMultiplier = 1 + (interestRate / 100);
    const calculated = totalAmount / interestMultiplier;
    form.original_amount = calculated.toFixed(2);
    calculatedField.value = `Capital Original: ${getCurrencySymbol(form.currency)}${form.original_amount}`;
  }

  else if (!form.total_amount && originalAmount > 0 && interestRate > 0) {
    // Calculate total amount: total = original * (1 + rate/100)
    const interestMultiplier = 1 + (interestRate / 100);
    const calculated = originalAmount * interestMultiplier;
    form.total_amount = calculated.toFixed(2);
    calculatedField.value = `Monto Total: ${getCurrencySymbol(form.currency)}${form.total_amount}`;
  }

  else if (!form.interest_rate && originalAmount > 0 && totalAmount > 0) {
    // Calculate interest rate: rate = ((total - original) / original) * 100
    const calculated = ((totalAmount - originalAmount) / originalAmount) * 100;
    form.interest_rate = calculated.toFixed(2);
    calculatedField.value = `Tasa de Interés: ${form.interest_rate}% anual`;
  }

  else if (!form.minimum_payment && originalAmount > 0 && totalAmount > 0) {
    // Calculate minimum payment (simplified: total / 12 months)
    const calculated = totalAmount / 12;
    form.minimum_payment = calculated.toFixed(2);
    calculatedField.value = `Pago Mensual: ${getCurrencySymbol(form.currency)}${form.minimum_payment}`;
  }

  else {
    // All fields are provided, validate consistency
    const calculatedTotal = originalAmount * (1 + (interestRate / 100));
    const difference = Math.abs(calculatedTotal - totalAmount);
    const tolerance = 0.01; // 1 cent tolerance

    if (difference > tolerance) {
      calculatedField.value = `⚠️ Advertencia: Los valores no son consistentes. Total calculado: ${getCurrencySymbol(form.currency)}${calculatedTotal.toFixed(2)}, pero ingresaste: ${getCurrencySymbol(form.currency)}${totalAmount.toFixed(2)}`;
    } else {
      calculatedField.value = `✅ Todos los campos están completos y son consistentes`;
    }
  }

  // Additional validation warnings
  if (interestRate > 200) {
    calculatedField.value += `\n⚠️ Advertencia: Tasa de interés muy alta (${interestRate}%). Considera refinanciar.`;
  }

  if (totalAmount < originalAmount) {
    calculatedField.value += `\n⚠️ Error: El monto total no puede ser menor que el capital original.`;
  }
}

// Clear calculated fields to allow recalculation
function clearCalculatedFields() {
  // Clear all fields to start fresh
  form.original_amount = '';
  form.total_amount = '';
  form.interest_rate = '';
  form.minimum_payment = '';
  calculatedField.value = '';
}

// Clear individual field
function clearField(fieldName) {
  form[fieldName] = '';
  calculatedField.value = '';
  // Recalculate if we still have enough fields
  if (fieldsProvided.value >= 3) {
    calculateMissingField();
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

  form.put(route('debts.update', debt.value.id));
}
</script>
