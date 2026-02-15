<template>
  <div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 text-gray-900">
          <h3 class="mb-4">Strategy: <span class="font-bold">{{ methodLabel }}</span></h3>
          <form @submit.prevent="submitForm" class="mb-6 flex items-center gap-4">
            <label for="extra_payment" class="font-semibold">Extra Payment:</label>
            <input type="number" v-model.number="extraPayment" id="extra_payment" min="0" step="1" class="border rounded px-2 py-1" />
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Apply</button>
          </form>
          <table class="min-w-full bg-white border mb-10">
            <thead>
              <tr>
                <th class="px-4 py-2 border">Nombre</th>
                <th class="px-4 py-2 border">Monto Total</th>
                <th class="px-4 py-2 border">Ya Pagado</th>
                <th class="px-4 py-2 border">Saldo Pendiente</th>
                <th class="px-4 py-2 border">Interés (%)</th>
                <th class="px-4 py-2 border">Pago mínimo</th>
                <th class="px-4 py-2 border">Meses restantes</th>
                <th class="px-4 py-2 border">Meses con extra</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="debt in plan" :key="debt.name">
                <td class="px-4 py-2 border">{{ debt.name }}</td>
                <td class="px-4 py-2 border">${{ Number(debt.amount).toFixed(2) }}</td>
                <td class="px-4 py-2 border">${{ Number(debt.amount_paid || 0).toFixed(2) }}</td>
                <td class="px-4 py-2 border">${{ Number(debt.amount - (debt.amount_paid || 0)).toFixed(2) }}</td>
                <td class="px-4 py-2 border">{{ debt.interest_rate }}</td>
                <td class="px-4 py-2 border">${{ Number(debt.minimum_payment).toFixed(2) }}</td>
                <td class="px-4 py-2 border">{{ debt.estimated_months === -1 ? 'Nunca' : debt.estimated_months }}</td>
                <td class="px-4 py-2 border">{{ debt.with_extra_payment === -1 ? 'Nunca' : debt.with_extra_payment }}</td>
              </tr>
              <tr v-if="plan.length === 0">
                <td colspan="8" class="px-4 py-2 border text-center">No se encontraron deudas.</td>
              </tr>
            </tbody>
          </table>
          <div class="mt-10">
            <h4 class="font-semibold mb-2">Comparación de meses para cada deuda</h4>
            <canvas id="debtMonthsChart" height="120"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
defineOptions({ layout: AppLayout });
import { ref, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Chart from 'chart.js/auto';

const props = defineProps({
  plan: Array,
  method: String,
  extra_payment: Number
});

const extraPayment = ref(props.extra_payment ?? 0);
const methodLabel = ref(props.method ? props.method.charAt(0).toUpperCase() + props.method.slice(1) : 'Snowball');

function submitForm() {
  router.get(route('debts.plan'), {
    method: props.method,
    extra_payment: extraPayment.value
  }, { preserveState: true, preserveScroll: true });
}

function renderChart() {
  try {
    const debtNames = props.plan.map(d => d.name);
    const monthsNormal = props.plan.map(d => parseInt(d.estimated_months) === -1 ? 0 : parseInt(d.estimated_months));
    const monthsExtra = props.plan.map(d => parseInt(d.with_extra_payment) === -1 ? 0 : parseInt(d.with_extra_payment));
    const ctx = document.getElementById('debtMonthsChart').getContext('2d');
    new Chart(ctx, {
    type: 'bar',
    data: {
      labels: debtNames,
      datasets: [
        {
          label: 'Meses sin pago extra',
          data: monthsNormal,
          backgroundColor: 'rgba(59, 130, 246, 0.7)',
        },
        {
          label: 'Meses con pago extra',
          data: monthsExtra,
          backgroundColor: 'rgba(16, 185, 129, 0.7)',
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position: 'top' },
        title: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          title: { display: true, text: 'Meses' }
        }
      }
    }
  });
  } catch (error) {
    console.error('Error rendering chart:', error);
  }
}

onMounted(() => {
  console.log('Component mounted, props:', props);
  console.log('Plan data:', props.plan);

  if (props.plan && props.plan.length > 0) {
    renderChart();
  } else {
    console.error('No plan data available for chart');
  }
});

watch(() => props.plan, (newPlan) => {
  console.log('Plan data changed:', newPlan);
  renderChart();
});
</script>
