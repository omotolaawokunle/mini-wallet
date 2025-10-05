import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { useAuthStore } from './auth'
import type {
    Transaction,
    TransferPayload,
    TransactionResponse,
    TransactionListResponse
} from '@/types/transaction'

export const useTransactionStore = defineStore('transaction', () => {
    const transactions = ref<Transaction[]>([])
    const isLoading = ref(false)
    const isSubmitting = ref(false)
    const error = ref<string | null>(null)
    const successMessage = ref<string | null>(null)
    const currentPage = ref(1)
    const lastPage = ref(1)
    const total = ref(0)

    const fetchTransactions = async (page: number = 1): Promise<void> => {
        try {
            isLoading.value = true
            error.value = null
            const response = await axios.get<TransactionListResponse>(`/api/transactions?page=${page}`)

            if (response.data.success && response.data.data) {
                transactions.value = response.data.data

                if (response.data.meta) {
                    currentPage.value = response.data.meta.current_page
                    lastPage.value = response.data.meta.last_page
                    total.value = response.data.meta.total
                }
            }
        } catch (err: any) {
            error.value = 'Failed to fetch transactions'
            console.error('Error fetching transactions:', err)
        } finally {
            isLoading.value = false
        }
    }

    const createTransfer = async (payload: TransferPayload): Promise<void> => {
        try {
            isSubmitting.value = true
            error.value = null
            successMessage.value = null

            const response = await axios.post<TransactionResponse>('/api/transactions', payload)

            if (response.data.success) {
                successMessage.value = response.data.message || 'Transfer initiated successfully!'
            }
        } catch (err: any) {
            if (err.response?.data?.message) {
                error.value = err.response.data.message
            } else {
                error.value = 'Transfer failed. Please try again.'
            }
            throw err
        } finally {
            isSubmitting.value = false
        }
    }

    const addTransaction = (transaction: Transaction) => {
        // Add to beginning of array
        transactions.value.unshift(transaction)
        total.value += 1

        // Update auth store balance if this affects current user
        const authStore = useAuthStore()
        if (authStore.user) {
            if (transaction.sender_id === authStore.user.id) {
                // Debit - subtract amount + commission
                const newBalance = authStore.user.balance - transaction.amount - transaction.commission_fee
                authStore.updateUser({ balance: newBalance })
            } else if (transaction.receiver_id === authStore.user.id) {
                // Credit - add amount
                const newBalance = authStore.user.balance + transaction.amount
                authStore.updateUser({ balance: newBalance })
            }
        }
    }

    const clearMessages = () => {
        error.value = null
        successMessage.value = null
    }

    const setError = (message: string) => {
        error.value = message
    }

    const setSuccessMessage = (message: string) => {
        successMessage.value = message
    }

    const resetStore = () => {
        transactions.value = []
        isLoading.value = false
        isSubmitting.value = false
        error.value = null
        successMessage.value = null
        currentPage.value = 1
        lastPage.value = 1
        total.value = 0
    }

    return {
        transactions: computed(() => transactions.value),
        isLoading: computed(() => isLoading.value),
        isSubmitting: computed(() => isSubmitting.value),
        error: computed(() => error.value),
        successMessage: computed(() => successMessage.value),
        currentPage: computed(() => currentPage.value),
        lastPage: computed(() => lastPage.value),
        total: computed(() => total.value),
        fetchTransactions,
        createTransfer,
        addTransaction,
        clearMessages,
        setError,
        setSuccessMessage,
        resetStore,
    }
})

