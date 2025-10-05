<template>
    <div class="min-h-screen bg-gray-50">
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
                <BalanceCard />
            </div>

            <!-- Transfer and Transactions Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- Transfer Form (1/3 width on large screens) -->
                <div class="lg:col-span-1">
                    <TransferForm />
                </div>

                <!-- Transaction History (2/3 width on large screens) -->
                <div class="lg:col-span-2">
                    <TransactionHistory />
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
    import BalanceCard from '@/components/BalanceCard.vue'
    import TransferForm from '@/components/TransferForm.vue'
    import TransactionHistory from '@/components/TransactionHistory.vue'
    import echo from '@/bootstrap'
    import type { Transaction } from '@/types/transaction'
    import type { User } from '@/types/auth'
    const router = useRouter()
    const authStore = useAuthStore()
    const transactionStore = useTransactionStore()

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
        // Fetch initial transactions
        await transactionStore.fetchTransactions()

        // Set up Pusher real-time updates
        if (authStore.user) {
            try {
                const channel = echo.private(`App.Models.User.${authStore.user.id}`)

                // Listen for transaction created events
                channel.listen('.transaction.created', (e: { transaction: Transaction }) => {
                    console.log('Transaction event received:', e)
                    transactionStore.setSuccessMessage('Transfer successfully completed')
                    transactionStore.addTransaction(e.transaction)
                })

                // Listen for transfer failed events
                channel.listen('.transfer.failed', (e: { sender_id: number, receiver_id: number, receiver: User, amount: number, commission_fee: number, message: string }) => {
                    console.log('Transfer failed event received:', e)
                    transactionStore.setError(`Failed to transfer money to User ID: ${e.receiver.id}. Reason: ${e.message}`)
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
