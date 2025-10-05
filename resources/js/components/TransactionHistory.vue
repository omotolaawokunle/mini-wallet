<template>
  <div class="bg-white rounded-lg shadow-md">
    <div class="p-6 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Transaction History</h2>
        <span v-if="transactionStore.total > 0" class="text-sm text-gray-500">
          {{ transactionStore.total }} {{ transactionStore.total === 1 ? 'transaction' : 'transactions' }}
        </span>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="transactionStore.isLoading" class="p-12 text-center">
      <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <p class="text-gray-500">Loading transactions...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="transactionStore.transactions.length === 0" class="p-12 text-center">
      <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions yet</h3>
      <p class="text-gray-500">Your transaction history will appear here once you send or receive money.</p>
    </div>

    <!-- Transaction List -->
    <div v-else class="divide-y divide-gray-200">
      <TransitionGroup
        name="transaction"
        tag="div"
      >
        <div
          v-for="transaction in transactionStore.transactions"
          :key="transaction.id"
          class="p-4 hover:bg-gray-50 transition-colors"
        >
          <div class="flex items-center justify-between">
            <!-- Left Side: Icon & Details -->
            <div class="flex items-center gap-3 flex-1">
              <!-- Icon -->
              <div :class="[
                'w-10 h-10 rounded-full flex items-center justify-center',
                transaction.type === 'Credit'
                  ? 'bg-green-100 text-green-600'
                  : 'bg-red-100 text-red-600'
              ]">
                <svg v-if="transaction.type === 'Credit'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                </svg>
              </div>

              <!-- Details -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                  <p class="text-sm font-medium text-gray-900 truncate">
                    {{ transaction.type === 'Credit' ? 'Received from' : 'Sent to' }}
                  </p>
                  <span :class="[
                    'px-2 py-0.5 text-xs font-medium rounded-full',
                    transaction.type === 'Credit'
                      ? 'bg-green-100 text-green-700'
                      : 'bg-red-100 text-red-700'
                  ]">
                    {{ transaction.type }}
                  </span>
                </div>
                <p class="text-sm text-gray-600 truncate">
                  {{ transaction.type === 'Credit' ? transaction.sender?.name : transaction.receiver?.name }}
                  <span class="text-gray-400">
                    (ID: {{ transaction.type === 'Credit' ? transaction.sender_id : transaction.receiver_id }})
                  </span>
                </p>
                <p class="text-xs text-gray-400 mt-1">
                  {{ formatDate(transaction.created_at) }}
                </p>
              </div>
            </div>

            <!-- Right Side: Amount -->
            <div class="text-right ml-4">
              <p :class="[
                'text-lg font-bold',
                transaction.type === 'Credit' ? 'text-green-600' : 'text-red-600'
              ]">
                {{ transaction.type === 'Credit' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
              </p>
              <p v-if="transaction.type === 'Debit'" class="text-xs text-gray-500">
                Fee: {{ formatCurrency(transaction.commission_fee) }}
              </p>
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- Pagination -->
    <div v-if="transactionStore.lastPage > 1" class="p-4 border-t border-gray-200 flex items-center justify-between">
      <button
        @click="previousPage"
        :disabled="transactionStore.currentPage === 1"
        :class="[
          'px-4 py-2 text-sm font-medium rounded-md transition-colors',
          transactionStore.currentPage === 1
            ? 'text-gray-400 cursor-not-allowed'
            : 'text-blue-600 hover:bg-blue-50'
        ]"
      >
        Previous
      </button>

      <span class="text-sm text-gray-600">
        Page {{ transactionStore.currentPage }} of {{ transactionStore.lastPage }}
      </span>

      <button
        @click="nextPage"
        :disabled="transactionStore.currentPage === transactionStore.lastPage"
        :class="[
          'px-4 py-2 text-sm font-medium rounded-md transition-colors',
          transactionStore.currentPage === transactionStore.lastPage
            ? 'text-gray-400 cursor-not-allowed'
            : 'text-blue-600 hover:bg-blue-50'
        ]"
      >
        Next
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useTransactionStore } from '@/stores/transaction'
import { formatCurrency } from '@/utils/currency'

const transactionStore = useTransactionStore()

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const diff = now.getTime() - date.getTime()

  // Less than 1 minute
  if (diff < 60000) {
    return 'Just now'
  }

  // Less than 1 hour
  if (diff < 3600000) {
    const minutes = Math.floor(diff / 60000)
    return `${minutes} ${minutes === 1 ? 'minute' : 'minutes'} ago`
  }

  // Less than 1 day
  if (diff < 86400000) {
    const hours = Math.floor(diff / 3600000)
    return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`
  }

  // Less than 1 week
  if (diff < 604800000) {
    const days = Math.floor(diff / 86400000)
    return `${days} ${days === 1 ? 'day' : 'days'} ago`
  }

  // Default format
  return date.toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const previousPage = async () => {
  if (transactionStore.currentPage > 1) {
    await transactionStore.fetchTransactions(transactionStore.currentPage - 1)
  }
}

const nextPage = async () => {
  if (transactionStore.currentPage < transactionStore.lastPage) {
    await transactionStore.fetchTransactions(transactionStore.currentPage + 1)
  }
}
</script>

<style scoped>
.transaction-enter-active {
  transition: all 0.5s ease;
}

.transaction-leave-active {
  transition: all 0.3s ease;
}

.transaction-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.transaction-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
</style>

