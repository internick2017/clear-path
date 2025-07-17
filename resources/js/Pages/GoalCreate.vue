<template>
  <div class="max-w-md mx-auto mt-12 bg-white p-8 rounded shadow">
    <h2 class="text-2xl font-bold mb-6">Crear nueva meta de ahorro</h2>
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="block mb-1 font-semibold">Título</label>
        <input v-model="form.title" type="text" maxlength="100" required class="border rounded px-3 py-2 w-full" />
        <div v-if="form.errors.title" class="text-red-600 text-sm mt-1">{{ form.errors.title }}</div>
      </div>
      <div>
        <label class="block mb-1 font-semibold">Monto meta</label>
        <input v-model.number="form.target_amount" type="number" step="0.01" min="1" required class="border rounded px-3 py-2 w-full" />
        <div v-if="form.errors.target_amount" class="text-red-600 text-sm mt-1">{{ form.errors.target_amount }}</div>
      </div>
      <div>
        <label class="block mb-1 font-semibold">Fecha límite</label>
        <input v-model="form.deadline" type="date" required class="border rounded px-3 py-2 w-full" :min="minDate" />
        <div v-if="form.errors.deadline" class="text-red-600 text-sm mt-1">{{ form.errors.deadline }}</div>
      </div>
      <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded w-full font-semibold">Crear meta</button>
    </form>
  </div>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: AppLayout })

const today = new Date()
const minDate = ref(today.toISOString().split('T')[0])

const form = useForm({
  title: '',
  target_amount: '',
  deadline: ''
})

function submit() {
  form.post(route('goals.store'))
}
</script>
