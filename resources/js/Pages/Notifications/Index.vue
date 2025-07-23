<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Notifications
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Header with actions -->
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    All Notifications
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ notifications.total }} total notifications
                                </p>
                            </div>
                            <div class="flex space-x-3">
                                <button
                                    @click="markAllAsRead"
                                    :disabled="loading"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                >
                                    <CheckIcon class="h-4 w-4 mr-2" />
                                    Mark All Read
                                </button>
                                <button
                                    @click="clearAll"
                                    :disabled="loading"
                                    class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                                >
                                    <TrashIcon class="h-4 w-4 mr-2" />
                                    Clear All
                                </button>
                            </div>
                        </div>

                        <!-- Notifications list -->
                        <div v-if="notifications.data.length > 0" class="space-y-4">
                            <div
                                v-for="notification in notifications.data"
                                :key="notification.id"
                                :class="[
                                    'border rounded-lg p-4 transition-all duration-200',
                                    notification.read_at
                                        ? 'bg-gray-50 border-gray-200'
                                        : 'bg-white border-blue-200 shadow-sm'
                                ]"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3">
                                            <!-- Notification icon -->
                                            <div
                                                :class="[
                                                    'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center',
                                                    getNotificationIcon(notification.data.type).bg
                                                ]"
                                            >
                                                <component
                                                    :is="getNotificationIcon(notification.data.type).icon"
                                                    class="w-4 h-4 text-white"
                                                />
                                            </div>

                                            <!-- Notification content -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="text-sm font-medium text-gray-900">
                                                        {{ notification.data.title || getNotificationTitle(notification.data.type) }}
                                                    </h4>
                                                    <span
                                                        v-if="!notification.read_at"
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                                    >
                                                        New
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    {{ notification.data.message }}
                                                </p>
                                                <div class="flex items-center space-x-4 mt-2 text-xs text-gray-500">
                                                    <span>
                                                        {{ formatDate(notification.created_at) }}
                                                    </span>
                                                    <span v-if="notification.data.category">
                                                        Category: {{ notification.data.category }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center space-x-2 ml-4">
                                        <button
                                            v-if="!notification.read_at"
                                            @click="markAsRead(notification.id)"
                                            :disabled="loading"
                                            class="text-gray-400 hover:text-gray-600 disabled:opacity-50"
                                            title="Mark as read"
                                        >
                                            <CheckIcon class="h-4 w-4" />
                                        </button>
                                        <button
                                            @click="deleteNotification(notification.id)"
                                            :disabled="loading"
                                            class="text-gray-400 hover:text-red-600 disabled:opacity-50"
                                            title="Delete notification"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty state -->
                        <div v-else class="text-center py-12">
                            <BellIcon class="mx-auto h-12 w-12 text-gray-400" />
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                You're all caught up! New notifications will appear here.
                            </p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="notifications.data.length > 0" class="mt-6">
                            <Pagination :links="notifications.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Pagination from '@/Components/Pagination.vue'
import {
    CheckIcon,
    TrashIcon,
    BellIcon,
    ExclamationTriangleIcon,
    CurrencyDollarIcon,
    FlagIcon,
    DocumentTextIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    notifications: Object
})

const loading = ref(false)

const markAsRead = async (id) => {
    loading.value = true
    try {
        await router.post(route('notifications.markAsRead', id))
        // Refresh the page to update the notification state
        router.reload()
    } catch (error) {
        console.error('Error marking notification as read:', error)
    } finally {
        loading.value = false
    }
}

const markAllAsRead = async () => {
    loading.value = true
    try {
        await router.post(route('notifications.markAllAsRead'))
        router.reload()
    } catch (error) {
        console.error('Error marking all notifications as read:', error)
    } finally {
        loading.value = false
    }
}

const deleteNotification = async (id) => {
    if (!confirm('Are you sure you want to delete this notification?')) {
        return
    }

    loading.value = true
    try {
        await router.delete(route('notifications.destroy', id))
        router.reload()
    } catch (error) {
        console.error('Error deleting notification:', error)
    } finally {
        loading.value = false
    }
}

const clearAll = async () => {
    if (!confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
        return
    }

    loading.value = true
    try {
        await router.delete(route('notifications.clear'))
        router.reload()
    } catch (error) {
        console.error('Error clearing notifications:', error)
    } finally {
        loading.value = false
    }
}

const formatDate = (dateString) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffInHours = (now - date) / (1000 * 60 * 60)

    if (diffInHours < 1) {
        return 'Just now'
    } else if (diffInHours < 24) {
        return `${Math.floor(diffInHours)} hours ago`
    } else if (diffInHours < 48) {
        return 'Yesterday'
    } else {
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        })
    }
}

const getNotificationIcon = (type) => {
    const icons = {
        'budget_exceeded': {
            icon: ExclamationTriangleIcon,
            bg: 'bg-red-500'
        },
        'goal_reached': {
            icon: FlagIcon,
            bg: 'bg-green-500'
        },
        'debt_payment_reminder': {
            icon: CurrencyDollarIcon,
            bg: 'bg-yellow-500'
        },
        'transaction_category_change': {
            icon: DocumentTextIcon,
            bg: 'bg-blue-500'
        },
        'default': {
            icon: BellIcon,
            bg: 'bg-gray-500'
        }
    }

    return icons[type] || icons.default
}

const getNotificationTitle = (type) => {
    const titles = {
        'budget_exceeded': 'Budget Exceeded',
        'goal_reached': 'Goal Reached!',
        'debt_payment_reminder': 'Debt Payment Reminder',
        'transaction_category_change': 'Transaction Category Changed',
        'default': 'Notification'
    }

    return titles[type] || titles.default
}
</script>