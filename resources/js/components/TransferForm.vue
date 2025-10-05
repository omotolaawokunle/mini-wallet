<template>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Send Money</h2>

        <form @submit.prevent="handleSubmit" class="space-y-4">
            <!-- Recipient User ID -->
            <div>
                <label for="receiver_id" class="block text-sm font-medium text-gray-700 mb-1">
                    Recipient User ID
                </label>
                <div class="relative">
                    <input id="receiver_id" v-model="form.receiver_id" type="text" inputmode="numeric"
                        placeholder="Enter recipient's user ID" @input="handleReceiverInput" :class="[
                            'w-full px-3 py-2 pr-10 border rounded-md transition-all duration-200',
                            'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                            errors.receiver_id
                                ? 'border-red-300 bg-red-50 focus:ring-red-500'
                                : isReceiverValidating
                                ? 'border-yellow-300 bg-yellow-50'
                                : receiverValid
                                ? 'border-green-300 bg-green-50'
                                : 'border-gray-300 bg-white'
]" :disabled="transactionStore.isSubmitting" />
                    <!-- Validation Status Icon -->
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <!-- Validating -->
                        <svg v-if="isReceiverValidating" class="animate-spin h-5 w-5 text-yellow-500" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <!-- Valid -->
                        <svg v-else-if="receiverValid && !errors.receiver_id" class="h-5 w-5 text-green-500"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <!-- Invalid -->
                        <svg v-else-if="errors.receiver_id" class="h-5 w-5 text-red-500" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <Transition enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
                    <p v-if="errors.receiver_id" class="mt-1 text-sm text-red-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ errors.receiver_id }}
                    </p>
                </Transition>
                <Transition enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
                    <p v-if="receiver" class="mt-1 text-sm text-gray-600 flex items-center gap-1">
                        Receiver: {{ receiver.name }}
                    </p>
                </Transition>
            </div>

            <!-- Amount -->
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">
                    Amount
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 text-base font-medium">$</span>
                    </div>
                    <input id="amount" v-model="displayAmount" type="text" inputmode="decimal" placeholder="0.00"
                        @input="handleAmountInput" @focus="handleAmountFocus" @blur="handleAmountBlur" :class="[
                            'w-full pl-8 pr-3 py-2 border rounded-md transition-all duration-200',
                            'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                            'text-lg font-medium',
                            errors.amount
                                ? 'border-red-300 bg-red-50 focus:ring-red-500'
                                : 'border-gray-300 bg-white'
]" :disabled="transactionStore.isSubmitting" />
                </div>
                <Transition enter-active-class="transition-all duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition-all duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
                    <p v-if="errors.amount" class="mt-1 text-sm text-red-600 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd" />
                        </svg>
                        {{ errors.amount }}
                    </p>
                </Transition>

                <!-- Commission Calculator Preview with Animation -->
                <Transition enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 -translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 -translate-y-2">
                    <div v-if="showCalculator"
                        class="mt-3 bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4 space-y-2 shadow-sm">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Amount to send:
                            </span>
                            <span class="font-semibold text-gray-900">{{ formatCurrency(numericAmount) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Commission (1.5%):
                            </span>
                            <span class="font-semibold text-gray-900">{{ formatCurrency(calculatedCommission) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-base border-t border-blue-300 pt-2 mt-2">
                            <span class="font-bold text-gray-800 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Total to be deducted:
                            </span>
                            <span class="font-bold text-blue-600 text-lg">{{ formatCurrency(totalAmount) }}</span>
                        </div>
                    </div>
                </Transition>

                <!-- Balance Check Warning with Animation -->
                <Transition enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 -translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 -translate-y-2">
                    <div v-if="hasInsufficientBalance"
                        class="mt-2 bg-gradient-to-r from-red-50 to-red-100 border border-red-300 rounded-lg p-3 shadow-sm">
                        <p class="text-sm text-red-700 flex items-center gap-2 font-medium">
                            <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            <span>
                                Insufficient balance! Available:
                                <strong>{{ formatCurrency(authStore.user?.balance) }}</strong>
                            </span>
                        </p>
                    </div>
                </Transition>
            </div>

            <!-- Submit Button with Loading State -->
            <button type="submit" :disabled="!canSubmit" :class="[
                'w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white transition-all duration-200',
                canSubmit
                    ? 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transform hover:scale-[1.02] active:scale-[0.98]'
                    : 'bg-gray-400 cursor-not-allowed'
            ]">
                <svg v-if="transactionStore.isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                {{ transactionStore.isSubmitting ? 'Processing Transfer...' : 'Send Money' }}
            </button>
        </form>
    </div>
</template>

<script setup lang="ts">
    import { ref, computed, watch } from 'vue'
    import { useAuthStore } from '@/stores/auth'
    import { useTransactionStore } from '@/stores/transaction'
    import { useToast } from '@/composables/useToast'
    import { useDebouncedFunction } from '@/composables/useDebounce'
    import { formatCurrency, parseAmount, calculateCommission, calculateTotal } from '@/utils/currency'
    import { extractValidationErrors } from '@/utils/errors'
    import type { TransferFormData, FormErrors } from '@/types/transaction'
    import type { User } from '@/types/auth'

    const authStore = useAuthStore()
    const transactionStore = useTransactionStore()
    const { success, error: showError } = useToast()

    const form = ref<TransferFormData>({
        receiver_id: '',
        amount: '',
    })

    const displayAmount = ref('')
    const errors = ref<FormErrors>({})
    const isReceiverValidating = ref(false)
    const receiverValid = ref(false)
    const isAmountFocused = ref(false)

    const numericAmount = computed(() => parseAmount(displayAmount.value))
    const isValidAmount = computed(() => numericAmount.value > 0)
    const calculatedCommission = computed(() => calculateCommission(numericAmount.value))
    const totalAmount = computed(() => calculateTotal(numericAmount.value))

    const showCalculator = computed(() => isValidAmount.value)

    const hasInsufficientBalance = computed(() => {
        if (!authStore.user || !isValidAmount.value) return false
        return totalAmount.value > authStore.user.balance
    })

    const canSubmit = computed(() => {
        return !transactionStore.isSubmitting &&
            !authStore.user?.is_flagged &&
            form.value.receiver_id.length > 0 &&
            receiverValid.value &&
            isValidAmount.value &&
            !hasInsufficientBalance.value &&
            Object.keys(errors.value).length === 0
    })

    const receiver = ref<User | null>(null)

// Debounced receiver validation
const validateReceiver = useDebouncedFunction(async () => {
    if (!form.value.receiver_id) {
        receiverValid.value = false
        isReceiverValidating.value = false
        return
    }

    if (!/^\d+$/.test(form.value.receiver_id)) {
        errors.value.receiver_id = 'User ID must be a number'
        receiverValid.value = false
        isReceiverValidating.value = false
        return
    }

    if (authStore.user && parseInt(form.value.receiver_id) === authStore.user.id) {
        errors.value.receiver_id = 'You cannot send money to yourself'
        receiverValid.value = false
        isReceiverValidating.value = false
        return
    }

    isReceiverValidating.value = true
    try {
        receiver.value = await transactionStore.validateReceiver(parseInt(form.value.receiver_id))
    } catch (err: any) {
        console.error(err)
        errors.value.receiver_id = err.response?.data?.errors?.receiver_id || 'Invalid receiver'
        receiverValid.value = false
        isReceiverValidating.value = false
        return
    }

    delete errors.value.receiver_id
    receiverValid.value = true
    isReceiverValidating.value = false
}, 800)

const handleReceiverInput = () => {
    receiverValid.value = false
    delete errors.value.receiver_id
    transactionStore.clearMessages()
    receiver.value = null
    validateReceiver()
}

const handleAmountInput = (e: Event) => {
    const input = e.target as HTMLInputElement
    let value = input.value.replace(/[^0-9.]/g, '')

    // Handle multiple decimal points
    const parts = value.split('.')
    if (parts.length > 2) {
        value = parts[0] + '.' + parts.slice(1).join('')
    }

    // Limit decimal places to 2
    if (parts.length === 2 && parts[1] && parts[1].length > 2) {
        value = parts[0] + '.' + parts[1].substring(0, 2)
    }

    displayAmount.value = value
    form.value.amount = value

    // Clear amount error
    delete errors.value.amount
    transactionStore.clearMessages()

    // Validate amount
    if (value && numericAmount.value <= 0) {
        errors.value.amount = 'Amount must be greater than 0'
    } else if (authStore.user && totalAmount.value > authStore.user.balance) {
        errors.value.amount = 'Insufficient balance for this transfer'
    }
}

const handleAmountFocus = () => {
    isAmountFocused.value = true
}

const handleAmountBlur = () => {
    isAmountFocused.value = false

    // Format on blur if there's a value
    if (displayAmount.value && numericAmount.value > 0) {
        displayAmount.value = numericAmount.value.toFixed(2)
    }
}

const handleSubmit = async () => {
    // Final validation
    if (!form.value.receiver_id) {
        errors.value.receiver_id = 'Recipient user ID is required'
        return
    }

    if (!isValidAmount.value) {
        errors.value.amount = 'Please enter a valid amount'
        return
    }

    if (hasInsufficientBalance.value) {
        errors.value.amount = 'Insufficient balance for this transfer'
        return
    }

    try {
        await transactionStore.createTransfer({
            receiver_id: parseInt(form.value.receiver_id),
            amount: numericAmount.value,
        })

        // Show success toast
        success(
            'Transfer Initiated!',
            `Sending ${formatCurrency(numericAmount.value)} to User ID ${form.value.receiver_id}`,
            4000
        )

        // Reset form
        form.value.receiver_id = ''
        form.value.amount = ''
        displayAmount.value = ''
        receiverValid.value = false
        errors.value = {}
    } catch (err: any) {
        const validationErrors = extractValidationErrors(err)
        errors.value = validationErrors

        // Show error toast
        showError(
            'Transfer Failed',
            err.response?.data?.message || 'Please check your inputs and try again',
            5000
        )
    }
}

// Watch for external success/error messages and show toasts
watch(() => transactionStore.successMessage, (msg: string | null) => {
    if (msg) {
        success('Success', msg, 4000)
    }
})

watch(() => transactionStore.error, (msg: string | null) => {
    if (msg) {
        showError('Error', msg, 5000)
    }
})
</script>

