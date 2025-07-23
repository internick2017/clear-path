<template>
    <div v-if="links.length > 3" class="flex items-center justify-between">
        <div class="flex-1 flex justify-between sm:hidden">
            <Link
                v-if="links[0].url"
                :href="links[0].url"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                Previous
            </Link>
            <Link
                v-if="links[links.length - 1].url"
                :href="links[links.length - 1].url"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            >
                Next
            </Link>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing
                    <span class="font-medium">{{ getCurrentPageInfo().from }}</span>
                    to
                    <span class="font-medium">{{ getCurrentPageInfo().to }}</span>
                    of
                    <span class="font-medium">{{ getCurrentPageInfo().total }}</span>
                    results
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <Link
                        v-for="(link, key) in links"
                        :key="key"
                        :href="link.url"
                        v-html="link.label"
                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium whitespace-nowrap"
                        :class="[
                            link.url === null
                                ? 'text-gray-300 cursor-default bg-gray-50 border-gray-300'
                                : link.active
                                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                        ]"
                    />
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    links: {
        type: Array,
        required: true
    }
})

const getCurrentPageInfo = () => {
    // This is a simplified version - in a real app you'd get this from the paginator
    // For now, we'll return placeholder data
    return {
        from: 1,
        to: 10,
        total: 100
    }
}
</script>