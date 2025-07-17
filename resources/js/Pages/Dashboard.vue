<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const budgets = usePage().props.budgets ?? [];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        You're logged in!
                        <h3 class="mt-6 mb-2 text-lg font-bold">Budgets Overview</h3>
                        <table class="min-w-full bg-white border mb-8">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 border">Category</th>
                                    <th class="px-4 py-2 border">Limit</th>
                                    <th class="px-4 py-2 border">Spent</th>
                                    <th class="px-4 py-2 border">Remaining</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="budget in budgets" :key="budget.id">
                                    <td class="px-4 py-2 border">{{ budget.category }}</td>
                                    <td class="px-4 py-2 border">${{ Number(budget.limit).toFixed(2) }}</td>
                                    <td class="px-4 py-2 border">${{ Number(budget.spent).toFixed(2) }}</td>
                                    <td class="px-4 py-2 border">${{ (Number(budget.limit) - Number(budget.spent)).toFixed(2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
