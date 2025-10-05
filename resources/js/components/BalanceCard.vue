<template>
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-medium text-blue-100">Current Balance</h3>
            <svg class="w-8 h-8 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
        </div>

        <div class="mb-2">
            <p class="text-4xl font-bold">
                {{ formatCurrency(authStore.user?.balance) }}
            </p>
        </div>

        <div class="flex items-center text-sm text-blue-100">
            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                    clip-rule="evenodd" />
            </svg>
            <span>Updated {{ lastUpdated }}</span>
        </div>

        <!-- Balance Change Indicator -->
        <Transition enter-active-class="transition-all duration-500" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-300"
            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
            <div v-if="showBalanceChange"
                class="mt-4 bg-white/20 rounded-md px-3 py-2 flex items-center justify-between">
                <span class="text-sm font-medium">
                    {{ balanceChangeType === 'increase' ? 'Received' : 'Sent' }}
                </span>
                <span :class="[
                    'text-sm font-bold flex items-center gap-1',
                    balanceChangeType === 'increase' ? 'text-green-200' : 'text-red-200'
                ]">
                    <svg v-if="balanceChangeType === 'increase'" class="w-4 h-4" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M16.707 10.293a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 111.414-1.414L9 14.586V3a1 1 0 012 0v11.586l4.293-4.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ formatCurrency(Math.abs(balanceChange)) }}
                </span>
            </div>
        </Transition>
    </div>
</template>

<script setup lang="ts">
    import { ref, watch } from 'vue'
    import { useAuthStore } from '@/stores/auth'
    import { formatCurrency } from '@/utils/currency'

    const authStore = useAuthStore()

    const lastUpdated = ref('just now')
    const previousBalance = ref<number>(0)
    const showBalanceChange = ref(false)
    const balanceChange = ref(0)
    const balanceChangeType = ref<'increase' | 'decrease'>('increase')

    // Initialize previous balance
    if (authStore.user) {
        previousBalance.value = authStore.user.balance
    }

    // Watch for balance changes
    watch(
        () => authStore.user?.balance,
        (newBalance: number | undefined, oldBalance: number | undefined) => {
            if (newBalance !== undefined && oldBalance !== undefined && newBalance !== oldBalance) {
                balanceChange.value = newBalance - oldBalance
                balanceChangeType.value = balanceChange.value > 0 ? 'increase' : 'decrease'
                showBalanceChange.value = true
                lastUpdated.value = 'just now'

                // Hide the indicator after 5 seconds
                setTimeout(() => {
                    showBalanceChange.value = false
                }, 5000)
            }
        }
    )

    // Update "last updated" text periodically
    setInterval(() => {
        if (lastUpdated.value === 'just now') {
            lastUpdated.value = 'a minute ago'
        }
    }, 60000) // Update every minute
</script>
