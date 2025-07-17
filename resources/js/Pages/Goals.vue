<template>
  <div class="py-12">
    <div v-if="success" class="mb-6 p-4 bg-green-100 text-green-800 rounded font-semibold text-center">
      {{ success }}
    </div>
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <h2 class="text-2xl font-bold mb-6">Metas de ahorro</h2>
          <div v-if="goals.length === 0" class="text-gray-500">No tienes metas registradas.</div>
          <div v-for="goal in goals" :key="goal.id" class="mb-8 border-b pb-6">
            <div class="flex justify-between items-center mb-2">
              <div>
                <h3 class="text-lg font-semibold">{{ goal.title }}</h3>
                <div class="text-sm text-gray-500">Fecha límite: {{ formatDate(goal.deadline) }}</div>
              </div>
              <div class="text-right">
                <span class="font-bold text-green-700">${{ Number(goal.current_amount).toFixed(2) }}</span>
                <span class="text-gray-500"> / ${{ Number(goal.target_amount).toFixed(2) }}</span>
              </div>
            </div>
            <div class="w-full bg-gray-200 rounded h-4 mb-2">
              <div :style="{ width: progress(goal) + '%' }" class="bg-blue-500 h-4 rounded"></div>
            </div>
            <div class="text-sm mb-2">Progreso: {{ progress(goal) }}%</div>
            <form @submit.prevent="addAmount(goal)" class="flex gap-2 items-center">
              <input type="number" v-model.number="amounts[goal.id]" min="1" step="0.01" placeholder="Agregar ahorro" class="border rounded px-2 py-1 w-32" />
              <button type="submit" class="bg-green-600 text-white px-4 py-1 rounded">Sumar</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const goals = page.props.goals ?? [];
const success = page.props.success ?? '';
const amounts = ref({});

function progress(goal) {
  if (!goal.target_amount || goal.target_amount == 0) return 0;
  return Math.min(100, Math.round((goal.current_amount / goal.target_amount) * 100));
}

function formatDate(date) {
  return new Date(date).toLocaleDateString();
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
</script>
