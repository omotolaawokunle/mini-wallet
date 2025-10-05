<template>
    <div class="animate-pulse">
        <!-- Transaction List Skeleton -->
        <div v-if="type === 'transaction-list'" class="space-y-3">
            <div v-for="i in count" :key="i"
                class="bg-white rounded-lg p-4 border border-gray-200 flex items-center justify-between">
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-10 h-10 bg-gray-300 rounded-full"></div>
                    <div class="flex-1 space-y-2">
                        <div class="h-4 bg-gray-300 rounded w-32"></div>
                        <div class="h-3 bg-gray-200 rounded w-24"></div>
                    </div>
                </div>
                <div class="space-y-2 text-right">
                    <div class="h-4 bg-gray-300 rounded w-20 ml-auto"></div>
                    <div class="h-3 bg-gray-200 rounded w-16 ml-auto"></div>
                </div>
            </div>
        </div>

        <!-- Balance Card Skeleton -->
        <div v-else-if="type === 'balance-card'" class="bg-gradient-to-br from-gray-300 to-gray-400 rounded-lg shadow-lg p-6">
            <div class="space-y-4">
                <div class="h-4 bg-gray-200/50 rounded w-32"></div>
                <div class="h-10 bg-gray-200/50 rounded w-48"></div>
                <div class="h-3 bg-gray-200/50 rounded w-24"></div>
            </div>
        </div>

        <!-- Form Skeleton -->
        <div v-else-if="type === 'form'" class="bg-white rounded-lg shadow-md p-6 space-y-4">
            <div class="h-6 bg-gray-300 rounded w-32 mb-4"></div>
            <div class="space-y-4">
                <div v-for="i in 2" :key="i" class="space-y-2">
                    <div class="h-4 bg-gray-300 rounded w-24"></div>
                    <div class="h-10 bg-gray-200 rounded w-full"></div>
                </div>
            </div>
            <div class="h-10 bg-gray-300 rounded w-full mt-6"></div>
        </div>

        <!-- Generic Skeleton -->
        <div v-else class="space-y-3">
            <div v-for="i in count" :key="i" class="h-12 bg-gray-300 rounded"></div>
        </div>
    </div>
</template>

<script setup lang="ts">
interface Props {
    type?: 'transaction-list' | 'balance-card' | 'form' | 'generic'
    count?: number
}

withDefaults(defineProps<Props>(), {
    type: 'generic',
    count: 3,
})
</script>

