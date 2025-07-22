<template>
  <div class="py-6 sm:py-12">
    <div v-if="success" class="mb-4 sm:mb-6 p-3 sm:p-4 bg-green-100 text-green-800 rounded font-semibold text-center text-sm">
      {{ success }}
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-4 sm:p-6 text-gray-900">
          <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 gap-4">
            <h2 class="text-xl sm:text-2xl font-bold">Metas de ahorro</h2>
            <Link :href="route('goals.create')" 
                  class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded text-sm sm:text-base text-center">
              Nueva Meta
            </Link>
          </div>
          
          <div v-if="goals.length === 0" class="text-gray-500 text-center py-8 sm:py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p class="text-lg mb-2">No tienes metas registradas.</p>
            <Link :href="route('goals.create')" class="inline-block text-green-600 hover:text-green-800 text-sm">
              Crear tu primera meta
            </Link>
          </div>
          
          <div v-else class="space-y-4 sm:space-y-6">
            <div v-for="goal in goals" :key="goal.id" class="border rounded-lg p-4 sm:p-6 hover:shadow-md transition-shadow">
              <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-4 gap-3">
                <div class="flex-1 min-w-0">
                  <div class="flex flex-col sm:flex-row sm:items-center gap-2 mb-2">
                    <h3 class="text-base sm:text-lg font-semibold truncate">{{ goal.title }}</h3>
                    <span v-if="progress(goal) >= 100" 
                          class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full self-start sm:self-auto">
                      Completed!
                    </span>
                  </div>
                  <div class="text-sm text-gray-500">Deadline: {{ formatDate(goal.deadline) }}</div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-2 self-start sm:self-auto">
                  <Link :href="route('goals.edit', goal.id)" 
                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium px-2 py-1 rounded hover:bg-indigo-50">
                    Editar
                  </Link>
                  <button @click="deleteGoal(goal)" 
                          class="text-red-600 hover:text-red-900 text-sm font-medium px-2 py-1 rounded hover:bg-red-50">
                    Eliminar
                  </button>
                </div>
              </div>
              
              <!-- Progress Display -->
              <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                  <span class="text-sm font-medium text-gray-700">Progreso</span>
                  <span class="text-sm font-medium text-gray-700">{{ progress(goal) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3">
                  <div :style="{ width: progress(goal) + '%' }" 
                       :class="progress(goal) >= 100 ? 'bg-green-500' : 'bg-blue-500'"
                       class="h-3 rounded-full transition-all duration-300"></div>
                </div>
              </div>
              
              <!-- Amount Display -->
              <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-2">
                <div class="text-right sm:text-left">
                  <span class="font-bold text-green-700 text-lg sm:text-xl">${{ Number(goal.current_amount).toFixed(2) }}</span>
                  <span class="text-gray-500 text-sm sm:text-base"> / ${{ Number(goal.target_amount).toFixed(2) }}</span>
                </div>
                <div class="text-sm text-gray-500 text-center sm:text-right">
                  {{ daysRemaining(goal) }} days remaining
                </div>
              </div>
              
              <!-- Manual Deposit Form -->
              <form @submit.prevent="addAmount(goal)" class="flex flex-col sm:flex-row gap-2 items-stretch sm:items-center">
                <div class="relative flex-1">
                  <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">$</span>
                  <input 
                    type="number" 
                    v-model.number="amounts[goal.id]" 
                    min="0.01" 
                    step="0.01"
                    placeholder="Agregar ahorro" 
                    class="w-full border border-gray-300 rounded-md pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm" 
                  />
                </div>
                <button type="submit" 
                        :disabled="!amounts[goal.id] || amounts[goal.id] <= 0"
                        class="bg-green-600 hover:bg-green-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white px-4 py-2 rounded font-medium text-sm transition-colors">
                  Sumar
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout })

const page = usePage();
const goals = page.props.goals ?? [];
const success = page.props.success ?? '';
const amounts = ref({});

function progress(goal) {
  if (!goal.target_amount || goal.target_amount == 0) return 0;
  return Math.min(100, Math.round((goal.current_amount / goal.target_amount) * 100));
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

function daysRemaining(goal) {
  const today = new Date();
  const deadline = new Date(goal.deadline);
  const diffTime = deadline - today;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays > 0 ? diffDays : 0;
}

function addAmount(goal) {
  const amount = amounts.value[goal.id];
  if (!amount || amount <= 0) return;
  router.post(route('goals.addAmount'), {
    goal_id: goal.id,
    amount: amount
  }, {
    preserveState: true,
    onSuccess: () => {
      amounts.value[goal.id] = '';
    }
  });
}

function deleteGoal(goal) {
        if (confirm(`Are you sure you want to delete the goal "${goal.title}"? This action cannot be undone.`)) {
    router.delete(route('goals.destroy', goal.id), {
      preserveState: true,
      preserveScroll: true
    });
  }
}
</script>
