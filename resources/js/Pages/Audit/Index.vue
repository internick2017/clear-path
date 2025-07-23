<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Audit Logs
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Header -->
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    Activity Log
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    Track all your financial activities for security and compliance
                                </p>
                            </div>
                        </div>

                        <!-- Audit logs list -->
                        <div v-if="logs.length > 0" class="space-y-4">
                            <div
                                v-for="log in logs"
                                :key="log.id"
                                class="border rounded-lg p-4 bg-gray-50"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <!-- Action icon -->
                                            <div
                                                :class="[
                                                    'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                                                    getActionIcon(log.action).bg
                                                ]"
                                            >
                                                <component
                                                    :is="getActionIcon(log.action).icon"
                                                    class="w-4 h-4 text-white"
                                                />
                                            </div>

                                            <!-- Log content -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="text-sm font-medium text-gray-900">
                                                        {{ log.description }}
                                                    </h4>
                                                    <span
                                                        :class="[
                                                            'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                                            getActionBadge(log.action).class
                                                        ]"
                                                    >
                                                        {{ getActionBadge(log.action).text }}
                                                    </span>
                                                </div>
                                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                                    <span>
                                                        {{ formatDate(log.created_at) }}
                                                    </span>
                                                    <span v-if="log.ip_address">
                                                        IP: {{ log.ip_address }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-else class="text-center py-12">
                            <DocumentTextIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No audit logs</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Your activity logs will appear here once you perform financial operations.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    DocumentTextIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    FlagIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    logs: {
        type: Array,
        required: true
    }
});

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};

const getActionIcon = (action) => {
    const icons = {
        'transaction_created': { icon: PlusIcon, bg: 'bg-green-500' },
        'transaction_updated': { icon: PencilIcon, bg: 'bg-blue-500' },
        'transaction_deleted': { icon: TrashIcon, bg: 'bg-red-500' },
        'budget_created': { icon: ChartBarIcon, bg: 'bg-green-500' },
        'budget_updated': { icon: PencilIcon, bg: 'bg-blue-500' },
        'budget_deleted': { icon: TrashIcon, bg: 'bg-red-500' },
        'goal_created': { icon: FlagIcon, bg: 'bg-green-500' },
        'goal_updated': { icon: PencilIcon, bg: 'bg-blue-500' },
        'goal_deleted': { icon: TrashIcon, bg: 'bg-red-500' },
        'goal_deposit': { icon: CurrencyDollarIcon, bg: 'bg-green-500' },
        'debt_created': { icon: PlusIcon, bg: 'bg-orange-500' },
        'debt_updated': { icon: PencilIcon, bg: 'bg-blue-500' },
        'debt_deleted': { icon: TrashIcon, bg: 'bg-red-500' },
        'debt_payment': { icon: CurrencyDollarIcon, bg: 'bg-green-500' },
        'debt_marked_paid': { icon: FlagIcon, bg: 'bg-green-500' },
        'debt_marked_active': { icon: FlagIcon, bg: 'bg-orange-500' },
    };

    return icons[action] || { icon: DocumentTextIcon, bg: 'bg-gray-500' };
};

const getActionBadge = (action) => {
    const badges = {
        'transaction_created': { text: 'Created', class: 'bg-green-100 text-green-800' },
        'transaction_updated': { text: 'Updated', class: 'bg-blue-100 text-blue-800' },
        'transaction_deleted': { text: 'Deleted', class: 'bg-red-100 text-red-800' },
        'budget_created': { text: 'Created', class: 'bg-green-100 text-green-800' },
        'budget_updated': { text: 'Updated', class: 'bg-blue-100 text-blue-800' },
        'budget_deleted': { text: 'Deleted', class: 'bg-red-100 text-red-800' },
        'goal_created': { text: 'Created', class: 'bg-green-100 text-green-800' },
        'goal_updated': { text: 'Updated', class: 'bg-blue-100 text-blue-800' },
        'goal_deleted': { text: 'Deleted', class: 'bg-red-100 text-red-800' },
        'goal_deposit': { text: 'Deposit', class: 'bg-green-100 text-green-800' },
        'debt_created': { text: 'Created', class: 'bg-orange-100 text-orange-800' },
        'debt_updated': { text: 'Updated', class: 'bg-blue-100 text-blue-800' },
        'debt_deleted': { text: 'Deleted', class: 'bg-red-100 text-red-800' },
        'debt_payment': { text: 'Payment', class: 'bg-green-100 text-green-800' },
        'debt_marked_paid': { text: 'Paid', class: 'bg-green-100 text-green-800' },
        'debt_marked_active': { text: 'Active', class: 'bg-orange-100 text-orange-800' },
    };

    return badges[action] || { text: 'Action', class: 'bg-gray-100 text-gray-800' };
};
</script>