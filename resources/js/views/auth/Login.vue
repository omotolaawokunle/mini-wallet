<template>
    <div
        class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-bold text-gray-900">
                    Sign in to your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Or
                    <router-link to="/register" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                        create a new account
                    </router-link>
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-8">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Email address
                        </label>
                        <input id="email" v-model="form.email" type="email" autocomplete="email"
                            @blur="validateField('email')" @input="clearFieldError('email')" :class="[
                                'w-full px-3 py-2 border rounded-md transition-colors',
                                'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                errors.email
                                    ? 'border-red-300 bg-red-50'
                                    : 'border-gray-300 bg-white'
                            ]" placeholder="john@example.com" />
                        <p v-if="errors.email" class="mt-1 text-sm text-red-600 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ errors.email }}
                        </p>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                        </label>
                        <div class="relative">
                            <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password" @blur="validateField('password')"
                                @input="clearFieldError('password')" :class="[
                                    'w-full px-3 py-2 pr-10 border rounded-md transition-colors',
                                    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                                    errors.password
                                        ? 'border-red-300 bg-red-50'
                                        : 'border-gray-300 bg-white'
                                ]" placeholder="••••••••" />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700 transition-colors">
                                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="errors.password" class="mt-1 text-sm text-red-600 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ errors.password }}
                        </p>
                    </div>

                    <!-- General Error -->
                    <div v-if="authStore.error" class="bg-red-50 border border-red-200 rounded-md p-3">
                        <p class="text-sm text-red-600 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ authStore.error }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" :disabled="isLoading || !isFormValid" :class="[
                        'w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white transition-all',
                        isLoading || !isFormValid
                            ? 'bg-gray-400 cursor-not-allowed'
                            : 'bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
                    ]">
                        <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        {{ isLoading ? 'Signing in...' : 'Sign in' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { extractValidationErrors } from '@/utils/errors'
import type { LoginCredentials, FormErrors } from '@/types/auth'

    const router = useRouter()
    const authStore = useAuthStore()

    const form = ref<LoginCredentials>({
        email: '',
        password: '',
    })

    const errors = ref<FormErrors>({})
    const showPassword = ref(false)
    const isLoading = ref(false)

    const isFormValid = computed(() => {
        return form.value.email.length > 0 &&
            form.value.password.length > 0 &&
            Object.keys(errors.value).length === 0
    })

    const validateField = (field: keyof LoginCredentials) => {
        errors.value = { ...errors.value }
        delete errors.value[field]
        authStore.clearError()

        if (field === 'email') {
            if (!form.value.email) {
                errors.value.email = 'Email is required'
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
                errors.value.email = 'Email is not valid'
            }
        }

        if (field === 'password') {
            if (!form.value.password) {
                errors.value.password = 'Password is required'
            }
        }
    }

    const clearFieldError = (field: string) => {
        if (errors.value[field]) {
            const newErrors = { ...errors.value }
            delete newErrors[field]
            errors.value = newErrors
        }
        authStore.clearError()
    }

    const handleSubmit = async () => {
        validateField('email')
        validateField('password')

        if (Object.keys(errors.value).length > 0) {
            return
        }

        try {
            isLoading.value = true
            await authStore.login(form.value)
            router.push({ name: 'dashboard' })
        } catch (err: any) {
            errors.value = extractValidationErrors(err)
        } finally {
            isLoading.value = false
        }
    }
</script>
