<template>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Send Money</h2>

        <form @submit.prevent="handleSubmit" class="space-y-4">
            <!-- Recipient User ID -->
            <div>
                <label for="receiver_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Recipient User ID
                </label>
                <input id="receiver_id" v-model="form.receiver_id" type="text" inputmode="numeric"
                    placeholder="Enter recipient's user ID" @blur="validateField('receiver_id')"
                    @input="clearFieldError('receiver_id')" :class="[
                        'w-full px-3 py-2 border rounded-md transition-colors',
                        'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                        errors.receiver_id
                            ? 'border-red-300 bg-red-50'
                            : 'border-gray-300 bg-white'
                    ]" :disabled="transactionStore.isSubmitting" />
                <p v-if="errors.receiver_id" class="mt-1 text-sm text-red-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ errors.receiver_id }}
                </p>
            </div>

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                    Amount
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">$</span>
                    </div>
                    <input id="amount" v-model="form.amount" type="text" inputmode="decimal" placeholder="0.00"
                        @blur="validateField('amount')" @input="onAmountInput" :class="[
                            'w-full pl-7 pr-3 py-2 border rounded-md transition-colors',
                            'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                            errors.amount
                                ? 'border-red-300 bg-red-50'
                                : 'border-gray-300 bg-white'
                        ]" :disabled="transactionStore.isSubmitting" />
                </div>
                <p v-if="errors.amount" class="mt-1 text-sm text-red-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ errors.amount }}
                </p>

                <!-- Transaction Summary -->
                <div v-if="isValidAmount" class="mt-3 bg-blue-50 border border-blue-200 rounded-md p-3 space-y-1">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Amount:</span>
                        <span class="font-medium text-gray-900">{{ formatCurrency(numericAmount) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Commission (1.5%):</span>
                        <span class="font-medium text-gray-900">{{ formatCurrency(calculatedCommission) }}</span>
                    </div>
                    <div class="flex justify-between text-sm border-t border-blue-200 pt-1 mt-1">
                        <span class="font-medium text-gray-700">Total:</span>
                        <span class="font-bold text-gray-900">{{ formatCurrency(totalAmount) }}</span>
                    </div>
                </div>

                <!-- Balance Check Warning -->
                <div v-if="isValidAmount && authStore.user && totalAmount > authStore.user.balance"
                    class="mt-2 bg-red-50 border border-red-200 rounded-md p-2">
                    <p class="text-sm text-red-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        Insufficient balance. Available: {{ formatCurrency(authStore.user.balance) }}
                    </p>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="transactionStore.error" class="bg-red-50 border border-red-200 rounded-md p-3">
                <p class="text-sm text-red-600 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ transactionStore.error }}
                </p>
            </div>

            <!-- Success Message -->
            <div v-if="transactionStore.successMessage" class="bg-green-50 border border-green-200 rounded-md p-3">
                <p class="text-sm text-green-600 flex items-center gap-2">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ transactionStore.successMessage }}
                </p>
            </div>

            <!-- Submit Button -->
            <button type="submit" :disabled="transactionStore.isSubmitting || !isFormValid || hasInsufficientBalance"
                :class="[
                    'w-full flex justify-center items-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white transition-all',
                    transactionStore.isSubmitting || !isFormValid || hasInsufficientBalance
                        ? 'bg-gray-400 cursor-not-allowed'
                        : 'bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
                ]">
                <svg v-if="transactionStore.isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                {{ transactionStore.isSubmitting ? 'Processing...' : 'Send Money' }}
            </button>
        </form>
    </div>
</template>

<script setup lang="ts">
    import { ref, computed } from 'vue'
    import { useAuthStore } from '@/stores/auth'
    import { useTransactionStore } from '@/stores/transaction'
    import { formatCurrency, parseAmount, calculateCommission, calculateTotal } from '@/utils/currency'
    import { extractValidationErrors } from '@/utils/errors'
    import type { TransferFormData, FormErrors } from '@/types/transaction'

    const authStore = useAuthStore()
    const transactionStore = useTransactionStore()

    const form = ref<TransferFormData>({
        receiver_id: '',
        amount: '',
    })

    const errors = ref<FormErrors>({})

    const numericAmount = computed(() => parseAmount(form.value.amount))
    const isValidAmount = computed(() => numericAmount.value > 0)
    const calculatedCommission = computed(() => calculateCommission(numericAmount.value))
    const totalAmount = computed(() => calculateTotal(numericAmount.value))

    const hasInsufficientBalance = computed(() => {
        if (!authStore.user || !isValidAmount.value) return false
        return totalAmount.value > authStore.user.balance
    })

    const isFormValid = computed(() => {
        return form.value.receiver_id.length > 0 &&
            isValidAmount.value &&
            Object.keys(errors.value).length === 0
    })

    const validateField = (field: keyof TransferFormData) => {
        errors.value = { ...errors.value }
        delete errors.value[field]

        if (field === 'receiver_id') {
            if (!form.value.receiver_id) {
                errors.value.receiver_id = 'Recipient user ID is required'
            } else if (!/^\d+$/.test(form.value.receiver_id)) {
                errors.value.receiver_id = 'User ID must be a number'
            } else if (authStore.user && parseInt(form.value.receiver_id) === authStore.user.id) {
                errors.value.receiver_id = 'You cannot send money to yourself'
            }
        }

        if (field === 'amount') {
            if (!form.value.amount) {
                errors.value.amount = 'Amount is required'
            } else if (numericAmount.value <= 0) {
                errors.value.amount = 'Amount must be greater than 0'
            } else if (authStore.user && totalAmount.value > authStore.user.balance) {
                errors.value.amount = 'Insufficient balance for this transfer'
            }
        }
    }

    const clearFieldError = (field: string) => {
        if (errors.value[field]) {
            const newErrors = { ...errors.value }
            delete newErrors[field]
            errors.value = newErrors
        }
        transactionStore.clearMessages()
    }

    const onAmountInput = () => {
        // Format amount input
        const value = form.value.amount.replace(/[^0-9.]/g, '')
        const parts = value.split('.')
        if (parts.length > 2) {
            form.value.amount = parts[0] + '.' + parts.slice(1).join('')
        } else {
            form.value.amount = value
        }

        clearFieldError('amount')
    }

    const handleSubmit = async () => {
        validateField('receiver_id')
        validateField('amount')

        if (Object.keys(errors.value).length > 0) {
            return
        }

        try {
            await transactionStore.createTransfer({
                receiver_id: parseInt(form.value.receiver_id),
                amount: numericAmount.value,
            })

            form.value.receiver_id = ''
            form.value.amount = ''
        } catch (err) {
            errors.value = extractValidationErrors(err)
        }
    }
</script>
