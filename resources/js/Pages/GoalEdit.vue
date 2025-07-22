<template>
  <div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Editar Meta de Ahorro</h2>
            <Link :href="route('goals.index')" class="text-gray-600 hover:text-gray-900">
              ← Volver a Metas
            </Link>
          </div>

          <form @submit.prevent="submit" class="space-y-6">
            <!-- Title -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Título de la Meta</label>
              <input 
                v-model="form.title" 
                type="text" 
                maxlength="100"
                placeholder="Ej: Vacaciones, Auto nuevo, Emergencias..."
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required 
              />
              <p class="text-sm text-gray-500 mt-1">
                Describe brevemente tu meta de ahorro.
              </p>
              <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</div>
            </div>

            <!-- Target Amount -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Monto Meta</label>
              <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">$</span>
                <input 
                  v-model.number="form.target_amount" 
                  type="number" 
                  step="0.01"
                  min="1"
                  placeholder="0.00"
                  class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  required 
                />
              </div>
              <p class="text-sm text-gray-500 mt-1">
                Establece el monto total que quieres ahorrar para esta meta.
              </p>
              <div v-if="form.errors.target_amount" class="text-red-600 text-sm mt-1">{{ form.errors.target_amount }}</div>
            </div>

            <!-- Current Amount (Read-only) -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Monto Actual</label>
              <div class="relative">
                <span class="absolute left-3 top-2 text-gray-500">$</span>
                <input 
                  :value="formatCurrency(goal.current_amount)"
                  type="text"
                  class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 bg-gray-50 text-gray-700"
                  readonly 
                />
              </div>
              <p class="text-sm text-gray-500 mt-1">
                Este es el monto que ya has ahorrado para esta meta.
              </p>
            </div>

            <!-- Deadline -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Límite</label>
              <input 
                v-model="form.deadline" 
                type="date"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required 
              />
              <p class="text-sm text-gray-500 mt-1">
                Establece la fecha límite para alcanzar tu meta.
              </p>
              <div v-if="form.errors.deadline" class="text-red-600 text-sm mt-1">{{ form.errors.deadline }}</div>
            </div>

            <!-- Progress Info -->
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
              <div class="flex items-start">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-blue-800">
                    Progreso Actual
                  </h3>
                  <div class="mt-2 text-sm text-blue-700">
                    <p>Has ahorrado ${{ formatCurrency(goal.current_amount) }} de ${{ formatCurrency(goal.target_amount) }}</p>
                    <p class="mt-1">Progreso: {{ progressPercentage }}%</p>
                    <div class="mt-2 w-full bg-blue-200 rounded-full h-2">
                      <div class="bg-blue-600 h-2 rounded-full" :style="{ width: Math.min(progressPercentage, 100) + '%' }"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end space-x-3">
              <Link :href="route('goals.index')" 
                    class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-2 px-4 rounded">
                Cancelar
              </Link>
              <button 
                type="submit" 
                :disabled="form.processing"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded disabled:opacity-50">
                {{ form.processing ? 'Actualizando...' : 'Actualizar Meta' }}
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
const goal = computed(() => page.props.goal);

// Format goal deadline for form
const goalDeadline = computed(() => {
  return goal.value.deadline ? new Date(goal.value.deadline).toISOString().split('T')[0] : '';
});

const form = useForm({
  title: goal.value.title,
  target_amount: goal.value.target_amount,
  deadline: goalDeadline.value
});

// Calculate progress percentage
const progressPercentage = computed(() => {
  if (!goal.value.target_amount || goal.value.target_amount == 0) return 0;
  return Math.min(100, Math.round((goal.value.current_amount / goal.value.target_amount) * 100));
});

// Helper function
function formatCurrency(amount) {
  return Number(amount).toFixed(2);
}

function submit() {
  form.put(route('goals.update', goal.value.id));
}
</script> 