<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Toast Container -->
        <ToastContainer />

        <!-- Navigation -->
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <svg class="w-8 h-8 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                        </svg>
                        <h1 class="text-xl font-bold text-gray-900">
                            Mini Wallet
                        </h1>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="hidden sm:block text-sm text-gray-700">
                            Welcome, <span class="font-medium">{{ authStore.user?.name }}</span>
                        </div>
                        <button @click="handleLogout" :disabled="authStore.isLoading"
                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ authStore.isLoading ? 'Logging out...' : 'Logout' }}
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Balance Card -->
            <div class="mb-6">
                <LoadingSkeleton v-if="isLoadingInitial" type="balance-card" />
                <BalanceCard v-else />
            </div>

            <!-- Transfer and Transactions Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Transfer Form (1/3 width on large screens) -->
                <div class="lg:col-span-1">
                    <LoadingSkeleton v-if="isLoadingInitial" type="form" />
                    <TransferForm v-else />
                </div>

                <!-- Transaction History (2/3 width on large screens) -->
                <div class="lg:col-span-2">
                    <LoadingSkeleton v-if="isLoadingInitial" type="transaction-list" :count="5" />
                    <TransactionHistory v-else />
                </div>
            </div>
        </main>
    </div>
</template>
<script setup lang="ts">
    import { onMounted, onUnmounted, ref } from 'vue'
    import { useRouter } from 'vue-router'
    import { useAuthStore } from '@/stores/auth'
    import { useTransactionStore } from '@/stores/transaction'
    import { useToast } from '@/composables/useToast'
    import BalanceCard from '@/components/BalanceCard.vue'
    import TransferForm from '@/components/TransferForm.vue'
    import TransactionHistory from '@/components/TransactionHistory.vue'
    import ToastContainer from '@/components/ToastContainer.vue'
    import LoadingSkeleton from '@/components/LoadingSkeleton.vue'
    import echo from '@/bootstrap'
    import type { Transaction } from '@/types/transaction'
    import type { User } from '@/types/auth'

    const router = useRouter()
    const authStore = useAuthStore()
    const transactionStore = useTransactionStore()
    const { success, error: showError } = useToast()
    const isLoadingInitial = ref(true)

    const handleLogout = async () => {
        try {
            await authStore.logout()
            transactionStore.resetStore()
            router.push({ name: 'login' })
        } catch (error) {
            console.error('Logout failed:', error)
        }
    }

    onMounted(async () => {
        // Fetch initial transactions with loading skeleton
        try {
            await transactionStore.fetchTransactions()
        } catch (error) {
            console.error('Error fetching transactions:', error)
            showError('Error', 'Failed to load transactions')
        } finally {
            // Show skeleton for minimum 800ms for smooth UX
            setTimeout(() => {
                isLoadingInitial.value = false
            }, 800)
        }

        // Set up Pusher real-time updates
        if (authStore.user) {
            try {
                const channel = echo.private(`App.Models.User.${authStore.user.id}`)

                // Listen for transaction created events
                channel.listen('.transaction.created', (e: { transaction: Transaction }) => {
                    console.log('Transaction event received:', e)
                    transactionStore.addTransaction(e.transaction)

                    // Show success toast
                    const isSender = e.transaction.sender_id === authStore.user?.id
                    if (isSender) {
                        success(
                            'Transfer Completed!',
                            `Successfully sent $${e.transaction.amount} to User ID ${e.transaction.receiver_id}`,
                            5000
                        )
                    } else {
                        success(
                            'Money Received!',
                            `You received $${e.transaction.amount} from User ID ${e.transaction.sender_id}`,
                            5000
                        )
                    }
                })

                // Listen for transfer failed events
                channel.listen('.transfer.failed', (e: { sender_id: number, receiver_id: number, receiver: User, amount: number, commission_fee: number, message: string }) => {
                    console.log('Transfer failed event received:', e)
                    showError(
                        'Transfer Failed',
                        `Failed to transfer $${e.amount} to User ID: ${e.receiver.id}. ${e.message}`,
                        6000
                    )
                })

            } catch (error) {
                console.error('Error setting up Pusher:', error)
            }
        }
    })

    onUnmounted(() => {
        // Clean up Pusher subscriptions
        if (authStore.user) {
            try {
                echo.leave(`App.Models.User.${authStore.user.id}`)
            } catch (error) {
                console.error('Error leaving channel:', error)
            }
        }
    })
</script>
