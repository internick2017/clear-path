<template>
  <div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Editar Transacción</h2>
            <Link :href="route('transactions.index')" class="text-gray-600 hover:text-gray-900">
              ← Volver a Transacciones
            </Link>
          </div>

          <form @submit.prevent="submit" class="space-y-6">
            <!-- Transaction Type -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Transacción</label>
              <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center p-4 border rounded-lg cursor-pointer transition-colors"
                       :class="form.type === 'income' ? 'border-green-500 bg-green-50' : 'border-gray-300 hover:border-gray-400'">
                  <input type="radio" v-model="form.type" value="income" class="mr-3" />
                  <div>
                    <div class="font-medium text-green-700">Ingreso</div>
                    <div class="text-sm text-green-600">Dinero que entra</div>
                  </div>
                </label>
                <label class="flex items-center p-4 border rounded-lg cursor-pointer transition-colors"
                       :class="form.type === 'expense' ? 'border-red-500 bg-red-50' : 'border-gray-300 hover:border-gray-400'">
                  <input type="radio" v-model="form.type" value="expense" class="mr-3" />
                  <div>
                    <div class="font-medium text-red-700">Gasto</div>
                    <div class="text-sm text-red-600">Dinero que sale</div>
                  </div>
                </label>
              </div>
              <div v-if="form.errors.type" class="text-red-600 text-sm mt-1">{{ form.errors.type }}</div>
            </div>

            <!-- Category -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
              <div class="relative">
                <input 
                  v-model="form.category" 
                  type="text" 
                  list="categories"
                  placeholder="Ej: Alimentación, Salario, Transporte..."
                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required 
                />
                <datalist id="categories">
                  <option v-for="category in categories" :key="category" :value="category">
                    {{ category }}
                  </option>
                </datalist>
              </div>
              <div v-if="form.errors.category" class="text-red-600 text-sm mt-1">{{ form.errors.category }}</div>
            </div>
            <div v-if="form.type === 'expense'">
              <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Gasto</label>
              <div class="grid grid-cols-2 gap-4">
                <label class="flex items-center p-4 border rounded-lg cursor-pointer transition-colors"
                       :class="form.expense_type === 'fixed' ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'">
                  <input type="radio" v-model="form.expense_type" value="fixed" class="mr-3" />
                  <div>
                    <div class="font-medium text-blue-700">Fijo</div>
                    <div class="text-sm text-blue-600">Gastos recurrentes (renta, servicios, etc.)</div>
                  </div>
                </label>
                <label class="flex items-center p-4 border rounded-lg cursor-pointer transition-colors"
                       :class="form.expense_type === 'variable' ? 'border-purple-500 bg-purple-50' : 'border-gray-300 hover:border-gray-400'">
                  <input type="radio" v-model="form.expense_type" value="variable" class="mr-3" />
                  <div>
                    <div class="font-medium text-purple-700">Variable</div>
                    <div class="text-sm text-purple-600">Gastos ocasionales (comida, entretenimiento, etc.)</div>
                  </div>
                </label>
              </div>
              <div v-if="form.errors.expense_type" class="text-red-600 text-sm mt-1">{{ form.errors.expense_type }}</div>
            </div>

            <!-- Debt Relationship Option (only for expenses) -->
            <div v-if="form.type === 'expense' && debts.length > 0">
              <label class="block text-sm font-medium text-gray-700 mb-2">Relación con Deudas</label>
              <div class="space-y-3">
                <!-- No debt relationship -->
                <label class="flex items-center p-3 border rounded-lg cursor-pointer transition-colors"
                       :class="!form.debt_id ? 'border-gray-500 bg-gray-50' : 'border-gray-300 hover:border-gray-400'">
                  <input type="radio" v-model="form.debt_id" value="" @change="form.is_debt_payment = false; form.is_debt_purchase = false" class="mr-3" />
                  <div>
                    <div class="font-medium text-gray-700">Sin relación con deudas</div>
                    <div class="text-sm text-gray-600">Gasto regular independiente</div>
                  </div>
                </label>
                
                <!-- Debt relationship options -->
                <div class="space-y-2">
                  <div v-for="debt in debts" :key="debt.id" class="border rounded-lg p-3">
                    <!-- Link to debt (tracking only) -->
                    <label class="flex items-center cursor-pointer transition-colors mb-2"
                           :class="form.debt_id == debt.id && !form.is_debt_payment && !form.is_debt_purchase ? 'text-blue-700' : 'text-gray-700'">
                      <input type="radio" v-model="form.debt_id" :value="debt.id" @change="form.is_debt_payment = false; form.is_debt_purchase = false" class="mr-3" />
                      <div class="flex-1">
                        <div class="font-medium">📋 Relacionar con: {{ debt.name }}</div>
                        <div class="text-sm text-gray-600">Solo para seguimiento, no afecta la deuda</div>
                      </div>
                    </label>
                    
                    <!-- Purchase with debt (increases balance) -->
                    <label class="flex items-center cursor-pointer transition-colors mb-2 ml-6 p-2 rounded"
                           :class="form.debt_id == debt.id && form.is_debt_purchase ? 'bg-red-50 border-red-200 text-red-700' : 'text-gray-600 hover:bg-gray-50'">
                      <input type="radio" 
                             :name="'debt-purchase-' + debt.id"
                             :value="true" 
                             @change="form.debt_id = debt.id; form.is_debt_purchase = true; form.is_debt_payment = false" 
                             class="mr-3" />
                      <div class="flex-1">
                        <div class="font-medium">💳 Compra con: {{ debt.name }}</div>
                        <div class="text-sm">Aumenta el saldo de la deuda</div>
                      </div>
                    </label>
                    
                    <!-- Actual debt payment -->
                    <label class="flex items-center cursor-pointer transition-colors ml-6 p-2 rounded"
                           :class="form.debt_id == debt.id && form.is_debt_payment ? 'bg-green-50 border-green-200 text-green-700' : 'text-gray-600 hover:bg-gray-50'">
                      <input type="radio" 
                             :name="'debt-payment-' + debt.id"
                             :value="true" 
                             @change="form.debt_id = debt.id; form.is_debt_payment = true; form.is_debt_purchase = false" 
                             class="mr-3" />
                      <div class="flex-1">
                        <div class="font-medium">💰 Pago de deuda: {{ debt.name }}</div>
                        <div class="text-sm">Reduce saldo restante: ${{ debt.amount }}</div>
                      </div>
                    </label>
                  </div>
                </div>
              </div>
              <div v-if="form.errors.debt_id" class="text-red-600 text-sm mt-1">{{ form.errors.debt_id }}</div>
              <div v-if="form.errors.is_debt_payment" class="text-red-600 text-sm mt-1">{{ form.errors.is_debt_payment }}</div>
              <div v-if="form.errors.is_debt_purchase" class="text-red-600 text-sm mt-1">{{ form.errors.is_debt_purchase }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Monto</label>
              <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">$</span>
                <input 
                  v-model.number="form.amount" 
                  type="number" 
                  step="0.01"
                  min="0.01"
                  placeholder="0.00"
                  class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required 
                />
              </div>
              <div v-if="form.errors.amount" class="text-red-600 text-sm mt-1">{{ form.errors.amount }}</div>
            </div>

            <!-- Date -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
              <input 
                v-model="form.date" 
                type="date"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required 
              />
              <div v-if="form.errors.date" class="text-red-600 text-sm mt-1">{{ form.errors.date }}</div>
            </div>

            <!-- Note -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Nota (Opcional)</label>
              <textarea 
                v-model="form.note" 
                rows="3"
                placeholder="Descripción adicional de la transacción..."
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              ></textarea>
              <div v-if="form.errors.note" class="text-red-600 text-sm mt-1">{{ form.errors.note }}</div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
              <Link :href="route('transactions.index')" 
                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
                Cancelar
              </Link>
              <button 
                type="submit" 
                :disabled="form.processing"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded disabled:opacity-50">
                {{ form.processing ? 'Actualizando...' : 'Actualizar Transacción' }}
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
const transaction = computed(() => page.props.transaction);
const categories = computed(() => page.props.categories || []);
const debts = computed(() => page.props.debts || []);

const form = useForm({
  type: transaction.value.type,
  category: transaction.value.category,
  amount: transaction.value.amount,
  date: transaction.value.date,
  note: transaction.value.note || '',
  expense_type: transaction.value.expense_type || '',
  debt_id: transaction.value.debt_id || '',
  is_debt_payment: false,
  is_debt_purchase: false
});

function submit() {
  form.put(route('transactions.update', transaction.value.id));
}
</script> 