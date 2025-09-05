<template>
  <div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Nueva Transacción</h2>
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
              <div class="mt-2 flex flex-wrap gap-2">
                <span class="text-xs text-gray-500">Sugerencias rápidas:</span>
                <button 
                  v-for="suggestion in popularCategories[form.type === 'expense' ? 'variable' : 'fixed']"
                  :key="suggestion"
                  @click="form.category = suggestion"
                  class="px-2 py-1 text-xs bg-gray-100 hover:bg-gray-200 rounded transition-colors"
                >
                  {{ suggestion }}
                </button>
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
                {{ form.processing ? 'Guardando...' : 'Crear Transacción' }}
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
import { ref, computed } from 'vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const categories = computed(() => page.props.categories || []);

// Popular categories for quick selection
const popularCategories = {
  fixed: [
    'Renta',
    'Luz',
    'Agua', 
    'Internet',
    'Transporte público',
    'Seguro de auto',
    'Teléfono',
    'Suscripciones'
  ],
  variable: [
    'Supermercado',
    'Restaurantes',
    'Gasolina',
    'Entretenimiento',
    'Ropa',
    'Farmacia',
    'Regalos',
    'Viajes'
  ]
};

// Set default date to today
const today = new Date().toISOString().split('T')[0];

const form = useForm({
  type: '',
  category: '',
  amount: '',
  date: today,
  note: '',
  expense_type: ''
});

function submit() {
  form.post(route('transactions.store'));
}
</script> 