<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, onUnmounted, watchEffect } from 'vue';
import { Chart, registerables } from 'chart.js';
import { useCurrency } from '@/composables/useCurrency.js';

Chart.register(...registerables);

defineOptions({ layout: AppLayout });

const page = usePage();
const { formatCurrency, getCurrencySymbol, setDefaultCurrency } = useCurrency();

// Get user's display currency
const userCurrency = computed(() => page.props.auth.user?.display_currency || 'USD');

// Watch for changes in user currency and update the composable
watchEffect(() => {
  setDefaultCurrency(userCurrency.value);
});

// Data from props
const budgets = computed(() => page.props.budgets || []);
const monthlySummary = computed(() => page.props.monthlySummary || { income: 0, expenses: 0, net: 0 });
const topExpenseCategories = computed(() => page.props.topExpenseCategories || []);
const activeGoals = computed(() => page.props.activeGoals || []);
const activeDebts = computed(() => page.props.activeDebts || []);
const recentTransactions = computed(() => page.props.recentTransactions || []);
const financialHealthScore = computed(() => page.props.financialHealthScore || null);
const chartData = computed(() => page.props.chartData || {});

// Chart refs
const monthlySpendingChart = ref(null);
const budgetVsActualChart = ref(null);
const goalProgressChart = ref(null);
const fixedVsVariableChart = ref(null);

// Chart instances for cleanup
let monthlySpendingChartInstance = null;
let budgetVsActualChartInstance = null;
let goalProgressChartInstance = null;
let fixedVsVariableChartInstance = null;

// Helper functions - formatCurrency is now from the composable

function formatDate(date) {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

// Initialize charts
onMounted(() => {
  console.log('Chart data received:', chartData.value);
  
  // Helper function to validate chart data
  const isValidChartData = (data) => {
    return data && 
           data.labels && 
           data.labels.length > 0 && 
           data.datasets && 
           data.datasets.length > 0 &&
           data.datasets.some(dataset => dataset.data && dataset.data.length > 0);
  };
  
  // Helper function to check if data has meaningful values
  const hasMeaningfulData = (data) => {
    if (!data.datasets) return false;
    return data.datasets.some(dataset => 
      dataset.data && 
      dataset.data.some(value => value > 0)
    );
  };
  
  // Monthly Spending Chart
  if (chartData.value.monthlySpending && monthlySpendingChart.value) {
    console.log('Creating monthly spending chart with data:', chartData.value.monthlySpending);
    
    // Validate data before creating chart
    if (isValidChartData(chartData.value.monthlySpending) && hasMeaningfulData(chartData.value.monthlySpending)) {
      try {
        monthlySpendingChartInstance = new Chart(monthlySpendingChart.value, {
          type: 'line',
          data: chartData.value.monthlySpending,
          options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,
            animation: {
              duration: 1000,
              easing: 'easeInOutQuart'
            },
            plugins: {
              legend: {
                position: 'top',
                labels: {
                  boxWidth: 12,
                  padding: 10,
                  font: {
                    size: 12
                  }
                }
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  font: {
                    size: 11
                  },
                  callback: function(value) {
                    return getCurrencySymbol(userCurrency.value) + value.toLocaleString();
                  }
                }
              },
              x: {
                ticks: {
                  font: {
                    size: 11
                  }
                }
              }
            },
            elements: {
              point: {
                radius: 4,
                hoverRadius: 6
              },
              line: {
                borderWidth: 2
              }
            }
          },
        });
      } catch (error) {
        console.error('Error creating monthly spending chart:', error);
      }
    } else {
      console.log('Monthly spending chart data is empty or invalid, skipping chart creation');
    }
  }

  // Budget vs Actual Chart
  if (chartData.value.budgetVsActual && budgetVsActualChart.value) {
    console.log('Creating budget vs actual chart with data:', chartData.value.budgetVsActual);
    
    // Validate data before creating chart
    if (isValidChartData(chartData.value.budgetVsActual) && hasMeaningfulData(chartData.value.budgetVsActual)) {
      try {
        budgetVsActualChartInstance = new Chart(budgetVsActualChart.value, {
          type: 'bar',
          data: chartData.value.budgetVsActual,
          options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 2,
            animation: {
              duration: 1000,
              easing: 'easeInOutQuart'
            },
            plugins: {
              legend: {
                position: 'top',
                labels: {
                  boxWidth: 12,
                  padding: 10,
                  font: {
                    size: 12
                  }
                }
              },
            },
            scales: {
              y: {
                beginAtZero: true,
                ticks: {
                  font: {
                    size: 11
                  },
                  callback: function(value) {
                    return getCurrencySymbol(userCurrency.value) + value.toLocaleString();
                  }
                }
              },
              x: {
                ticks: {
                  font: {
                    size: 11
                  }
                }
              }
            },
            elements: {
              bar: {
                borderWidth: 1
              }
            }
          },
        });
      } catch (error) {
        console.error('Error creating budget vs actual chart:', error);
      }
    } else {
      console.log('Budget vs actual chart data is empty or invalid, skipping chart creation');
    }
  }

  // Fixed vs Variable Chart
  if (chartData.value.fixedVsVariable && fixedVsVariableChart.value) {
    console.log('Creating fixed vs variable chart with data:', chartData.value.fixedVsVariable);
    
    // Validate data before creating chart
    if (isValidChartData(chartData.value.fixedVsVariable) && hasMeaningfulData(chartData.value.fixedVsVariable)) {
      try {
        fixedVsVariableChartInstance = new Chart(fixedVsVariableChart.value, {
          type: 'pie',
          data: chartData.value.fixedVsVariable,
          options: {
            responsive: true,
            maintainAspectRatio: true,
            aspectRatio: 1.5,
            animation: {
              duration: 1000,
              easing: 'easeInOutQuart'
            },
            plugins: {
              legend: {
                position: 'bottom',
                labels: {
                  boxWidth: 12,
                  padding: 10,
                  font: {
                    size: 12
                  }
                }
              },
              tooltip: {
                callbacks: {
                  label: function(context) {
                    return context.label + ': ' + getCurrencySymbol(userCurrency.value) + context.parsed.toLocaleString();
                  }
                }
              }
            },
            elements: {
              arc: {
                borderWidth: 2
              }
            }
          },
        });
      } catch (error) {
        console.error('Error creating fixed vs variable chart:', error);
      }
    } else {
      console.log('Fixed vs variable chart data is empty or invalid, skipping chart creation');
    }
  }
});

// Cleanup charts on unmount
onUnmounted(() => {
  if (monthlySpendingChartInstance) {
    monthlySpendingChartInstance.destroy();
  }
  if (budgetVsActualChartInstance) {
    budgetVsActualChartInstance.destroy();
  }
  if (goalProgressChartInstance) {
    goalProgressChartInstance.destroy();
  }
  if (fixedVsVariableChartInstance) {
    fixedVsVariableChartInstance.destroy();
  }
});
</script>

<template>
  <div class="py-6 sm:py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Financial Dashboard</h1>
        <p class="mt-2 text-sm sm:text-base text-gray-600">Your complete financial overview</p>
      </div>

      <!-- Monthly Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-4 sm:p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 sm:h-8 sm:w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div class="ml-3 sm:ml-4">
                <p class="text-xs sm:text-sm font-medium text-gray-500">Monthly Income</p>
                <p class="text-lg sm:text-2xl font-bold text-green-600">{{ formatCurrency(monthlySummary.income, userCurrency) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-4 sm:p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 sm:h-8 sm:w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                </svg>
              </div>
              <div class="ml-3 sm:ml-4">
                <p class="text-xs sm:text-sm font-medium text-gray-500">Monthly Expenses</p>
                <p class="text-lg sm:text-2xl font-bold text-red-600">{{ formatCurrency(monthlySummary.expenses, userCurrency) }}</p>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg sm:col-span-2 lg:col-span-1">
          <div class="p-4 sm:p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 sm:h-8 sm:w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
              </div>
              <div class="ml-3 sm:ml-4">
                <p class="text-xs sm:text-sm font-medium text-gray-500">Net Income</p>
                <p class="text-lg sm:text-2xl font-bold" :class="monthlySummary.net >= 0 ? 'text-blue-600' : 'text-red-600'">
                  {{ formatCurrency(monthlySummary.net, userCurrency) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Financial Health Score -->
      <div v-if="financialHealthScore" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 sm:mb-8">
        <div class="p-4 sm:p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-base sm:text-lg font-semibold">Puntuación de Salud Financiera</h3>
            <div class="flex items-center space-x-2">
              <span :class="[
                'px-3 py-1 rounded-full text-sm font-semibold',
                financialHealthScore.grade.color === 'green' ? 'bg-green-100 text-green-800' :
                financialHealthScore.grade.color === 'blue' ? 'bg-blue-100 text-blue-800' :
                financialHealthScore.grade.color === 'yellow' ? 'bg-yellow-100 text-yellow-800' :
                financialHealthScore.grade.color === 'orange' ? 'bg-orange-100 text-orange-800' :
                'bg-red-100 text-red-800'
              ]">
                {{ financialHealthScore.grade.grade }} - {{ financialHealthScore.grade.label }}
              </span>
            </div>
          </div>
          
          <div class="flex items-center space-x-4">
            <div class="flex-1">
              <div class="w-full bg-gray-200 rounded-full h-4">
                <div 
                  class="h-4 rounded-full transition-all duration-1000 ease-out"
                  :class="[
                    financialHealthScore.grade.color === 'green' ? 'bg-green-500' :
                    financialHealthScore.grade.color === 'blue' ? 'bg-blue-500' :
                    financialHealthScore.grade.color === 'yellow' ? 'bg-yellow-500' :
                    financialHealthScore.grade.color === 'orange' ? 'bg-orange-500' :
                    'bg-red-500'
                  ]"
                  :style="{ width: financialHealthScore.percentage + '%' }"
                ></div>
              </div>
              <div class="flex justify-between text-xs text-gray-500 mt-1">
                <span>0</span>
                <span>{{ financialHealthScore.score }}/100</span>
                <span>100</span>
              </div>
            </div>
            <div class="text-2xl sm:text-3xl font-bold" :class="[
              financialHealthScore.grade.color === 'green' ? 'text-green-600' :
              financialHealthScore.grade.color === 'blue' ? 'text-blue-600' :
              financialHealthScore.grade.color === 'yellow' ? 'text-yellow-600' :
              financialHealthScore.grade.color === 'orange' ? 'text-orange-600' :
              'text-red-600'
            ]">
              {{ financialHealthScore.score }}
            </div>
          </div>
          
          <div class="mt-4 grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs">
            <div class="text-center">
              <div class="font-medium text-gray-900">{{ financialHealthScore.breakdown.income_expense_ratio.toFixed(1) }}%</div>
              <div class="text-gray-500">Gastos/Ingresos</div>
            </div>
            <div class="text-center">
              <div class="font-medium text-gray-900">{{ financialHealthScore.breakdown.budget_compliance.toFixed(1) }}%</div>
              <div class="text-gray-500">Cumplimiento Presupuesto</div>
            </div>
            <div class="text-center">
              <div class="font-medium text-gray-900">{{ financialHealthScore.breakdown.savings_rate.toFixed(1) }}%</div>
              <div class="text-gray-500">Tasa de Ahorro</div>
            </div>
            <div class="text-center">
              <div class="font-medium text-gray-900">{{ financialHealthScore.breakdown.debt_ratio.toFixed(1) }}%</div>
              <div class="text-gray-500">Ratio Deuda/Ingresos</div>
            </div>
            <div class="text-center">
              <div class="font-medium text-gray-900">{{ financialHealthScore.breakdown.goal_progress.toFixed(1) }}%</div>
              <div class="text-gray-500">Progreso Metas</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 mb-6 sm:mb-8">
        <!-- Monthly Spending Chart -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold mb-4">Monthly Income vs Expenses</h3>
            <div v-if="!chartData.monthlySpending || !chartData.monthlySpending.labels || chartData.monthlySpending.labels.length === 0" 
                 class="flex items-center justify-center h-64 text-gray-500">
              <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <p class="text-sm">No transaction data available</p>
                <p class="text-xs">Add some transactions to see your spending trends</p>
              </div>
            </div>
            <div v-else class="relative w-full" style="max-height: 400px;">
              <canvas ref="monthlySpendingChart" class="w-full h-auto"></canvas>
            </div>
          </div>
        </div>

        <!-- Budget vs Actual Chart -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-4 sm:p-6">
            <h3 class="text-base sm:text-lg font-semibold mb-4">Budget vs Actual Spending</h3>
            <div v-if="!chartData.budgetVsActual || !chartData.budgetVsActual.labels || chartData.budgetVsActual.labels.length === 0" 
                 class="flex items-center justify-center h-64 text-gray-500">
              <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                <p class="text-sm">No budget data available</p>
                <p class="text-xs">Create some budgets to see your spending vs limits</p>
              </div>
            </div>
            <div v-else class="relative w-full" style="max-height: 400px;">
              <canvas ref="budgetVsActualChart" class="w-full h-auto"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Fixed vs Variable Expenses Chart -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-4 sm:p-6">
          <h3 class="text-base sm:text-lg font-semibold mb-4">Gastos Fijos vs Variables</h3>
          <div v-if="!chartData.fixedVsVariable || !chartData.fixedVsVariable.datasets || chartData.fixedVsVariable.datasets[0].data.every(val => val === 0)" 
               class="flex items-center justify-center h-64 text-gray-500">
            <div class="text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
              </svg>
              <p class="text-sm">No expense type data available</p>
              <p class="text-xs">Add transactions with fixed/variable classification</p>
            </div>
          </div>
          <div v-else class="relative w-full max-w-md mx-auto" style="max-height: 400px;">
            <canvas ref="fixedVsVariableChart" class="w-full h-auto"></canvas>
          </div>
        </div>
      </div>

      <!-- Goals Progress Chart -->
      <div v-if="activeGoals.length > 0" class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 sm:mb-8">
        <div class="p-4 sm:p-6">
          <h3 class="text-base sm:text-lg font-semibold mb-4">Goal Progress</h3>
          <div v-if="!chartData.goalProgress || !chartData.goalProgress.labels || chartData.goalProgress.labels.length === 0" 
               class="flex items-center justify-center h-64 text-gray-500">
            <div class="text-center">
              <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <p class="text-sm">No goal progress data available</p>
            </div>
          </div>
          <div v-else class="relative w-full max-w-md mx-auto" style="max-height: 400px;">
            <canvas ref="goalProgressChart" class="w-full h-auto"></canvas>
          </div>
        </div>
      </div>

      <!-- Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8">
        <!-- Budgets Overview -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-4 sm:p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-base sm:text-lg font-semibold">Budgets Overview</h3>
              <Link :href="route('budgets.index')" class="text-blue-600 hover:text-blue-800 text-xs sm:text-sm">
                View All
              </Link>
            </div>
            
            <div v-if="budgets.length === 0" class="text-gray-500 text-center py-4 text-sm">
              No budgets set for this month
            </div>
            
            <div v-else class="space-y-3 sm:space-y-4">
              <div v-for="budget in budgets" :key="budget.id" class="border-l-4 p-3" :class="budget.is_exceeded ? 'border-red-500 bg-red-50' : 'border-green-500 bg-green-50'">
                <div class="flex justify-between items-start">
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 text-sm sm:text-base truncate">{{ budget.category }}</p>
                    <p class="text-xs sm:text-sm text-gray-600">
                      {{ formatCurrency(budget.spent, userCurrency) }} / {{ formatCurrency(budget.limit, userCurrency) }}
                    </p>
                  </div>
                  <span class="text-xs sm:text-sm font-medium ml-2" :class="budget.is_exceeded ? 'text-red-600' : 'text-green-600'">
                    {{ budget.percentage.toFixed(1) }}%
                  </span>
                </div>
                <div class="mt-2 w-full bg-gray-200 rounded-full h-2">
                  <div class="h-2 rounded-full" :class="budget.is_exceeded ? 'bg-red-500' : 'bg-green-500'" 
                       :style="{ width: Math.min(budget.percentage, 100) + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Active Goals -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-4 sm:p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-base sm:text-lg font-semibold">Active Goals</h3>
              <Link :href="route('goals.index')" class="text-blue-600 hover:text-blue-800 text-xs sm:text-sm">
                View All
              </Link>
            </div>
            
            <div v-if="activeGoals.length === 0" class="text-gray-500 text-center py-4 text-sm">
              No active goals
            </div>
            
            <div v-else class="space-y-3 sm:space-y-4">
              <div v-for="goal in activeGoals" :key="goal.id" class="border rounded-lg p-3">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-medium text-gray-900 text-sm sm:text-base truncate">{{ goal.title }}</h4>
                  <span class="text-xs text-gray-500 ml-2">{{ goal.days_remaining }} days left</span>
                </div>
                <p class="text-xs sm:text-sm text-gray-600 mb-2">
                  {{ formatCurrency(goal.current_amount, userCurrency) }} / {{ formatCurrency(goal.target_amount, userCurrency) }}
                </p>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="h-2 rounded-full bg-blue-500" :style="{ width: goal.progress_percentage + '%' }"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ goal.progress_percentage.toFixed(1) }}% complete</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Active Debts -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-4 sm:p-6">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-base sm:text-lg font-semibold">Active Debts</h3>
              <Link :href="route('debts.index')" class="text-blue-600 hover:text-blue-800 text-xs sm:text-sm">
                View All
              </Link>
            </div>
            
            <div v-if="activeDebts.length === 0" class="text-gray-500 text-center py-4 text-sm">
              No active debts
            </div>
            
            <div v-else class="space-y-3 sm:space-y-4">
              <div v-for="debt in activeDebts" :key="debt.id" class="border rounded-lg p-3">
                <div class="flex justify-between items-start mb-2">
                  <h4 class="font-medium text-gray-900 text-sm sm:text-base truncate">{{ debt.name }}</h4>
                  <span class="text-xs text-gray-500 ml-2">{{ debt.interest_rate }}%</span>
                </div>
                <p class="text-xs sm:text-sm text-gray-600 mb-2">
                  {{ formatCurrency(debt.remaining_balance, userCurrency) }} remaining
                </p>
                <div class="w-full bg-gray-200 rounded-full h-2">
                  <div class="h-2 rounded-full bg-yellow-500" :style="{ width: debt.payment_progress + '%' }"></div>
                </div>
                <p class="text-xs text-gray-500 mt-1">{{ debt.payment_progress.toFixed(1) }}% paid</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Transactions -->
      <div class="mt-6 sm:mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-4 sm:p-6">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-base sm:text-lg font-semibold">Recent Transactions</h3>
            <Link :href="route('transactions.index')" class="text-blue-600 hover:text-blue-800 text-xs sm:text-sm">
              View All
            </Link>
          </div>
          
          <div v-if="recentTransactions.length === 0" class="text-gray-500 text-center py-4 text-sm">
            No recent transactions
          </div>
          
          <div v-else class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                  <th class="px-3 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                  <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Note</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="transaction in recentTransactions" :key="transaction.id">
                  <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                    {{ formatDate(transaction.date) }}
                  </td>
                  <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                    {{ transaction.category }}
                  </td>
                  <td class="px-3 sm:px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                          :class="transaction.type === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                      {{ transaction.type }}
                    </span>
                  </td>
                  <td class="px-3 sm:px-6 py-4 whitespace-nowrap text-xs sm:text-sm font-medium" 
                      :class="transaction.type === 'income' ? 'text-green-600' : 'text-red-600'">
                    {{ formatCurrency(transaction.amount, userCurrency) }}
                  </td>
                  <td class="hidden sm:table-cell px-6 py-4 text-xs sm:text-sm text-gray-500">
                    {{ transaction.note || '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
