import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import type { User, LoginCredentials, RegisterCredentials, AuthResponse } from '@/types/auth'

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null)
    const isLoading = ref(false)
    const error = ref<string | null>(null)
    const isAuthenticated = computed(() => user.value !== null)

    const fetchUser = async (): Promise<void> => {
        try {
            isLoading.value = true
            error.value = null

            const response = await axios.get<AuthResponse>('/api/user')

            if (response.data.success && response.data.data) {
                user.value = response.data.data.user
            }
        } catch (err: any) {
            if (err.response?.status !== 401) {
                error.value = 'Failed to fetch user'
            }
            user.value = null
        } finally {
            isLoading.value = false
        }
    }

    const login = async (credentials: LoginCredentials): Promise<void> => {
        try {
            isLoading.value = true
            error.value = null
            await axios.get('/sanctum/csrf-cookie')
            const response = await axios.post<AuthResponse>('/login', credentials)

            if (response.data.success && response.data.data) {
                user.value = response.data.data.user
            }
        } catch (err: any) {
            if (err.response?.data?.message) {
                error.value = err.response.data.message
            } else {
                error.value = 'Login failed. Please try again.'
            }
            throw err
        } finally {
            isLoading.value = false
        }
    }

    const register = async (credentials: RegisterCredentials): Promise<void> => {
        try {
            isLoading.value = true
            error.value = null
            await axios.get('/sanctum/csrf-cookie')
            const response = await axios.post<AuthResponse>('/register', credentials)

            if (response.data.success && response.data.data) {
                user.value = response.data.data.user
            }
        } catch (err: any) {
            if (err.response?.data?.message) {
                error.value = err.response.data.message
            } else {
                error.value = 'Registration failed. Please try again.'
            }
            throw err
        } finally {
            isLoading.value = false
        }
    }

    const logout = async (): Promise<void> => {
        try {
            isLoading.value = true
            error.value = null

            await axios.post('/logout')
            user.value = null
        } catch (err: any) {
            error.value = 'Logout failed. Please try again.'
            throw err
        } finally {
            isLoading.value = false
        }
    }

    const clearError = () => {
        error.value = null
    }

    const resetStore = () => {
        user.value = null
        isLoading.value = false
        error.value = null
    }

    const updateUser = (updatedUser: Partial<User>) => {
        user.value = { ...user.value, ...updatedUser } as User
    }

    return {
        user: computed(() => user.value),
        isAuthenticated,
        isLoading: computed(() => isLoading.value),
        error: computed(() => error.value),
        fetchUser,
        login,
        register,
        logout,
        clearError,
        resetStore,
        updateUser,
    }
})

