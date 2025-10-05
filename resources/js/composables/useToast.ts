import { ref } from 'vue'

export interface ToastNotification {
    id: string
    type: 'success' | 'error' | 'info'
    title: string
    message?: string
    duration?: number
}

const toasts = ref<ToastNotification[]>([])

export function useToast() {
    const showToast = (notification: Omit<ToastNotification, 'id'>) => {
        const id = `toast-${Date.now()}-${Math.random()}`
        const toast: ToastNotification = {
            id,
            ...notification,
            duration: notification.duration || 5000,
        }

        toasts.value.push(toast)

        // Auto remove after duration
        setTimeout(() => {
            removeToast(id)
        }, toast.duration)

        return id
    }

    const removeToast = (id: string) => {
        toasts.value = toasts.value.filter(t => t.id !== id)
    }

    const success = (title: string, message?: string, duration?: number) => {
        return showToast({ type: 'success', title, message, duration })
    }

    const error = (title: string, message?: string, duration?: number) => {
        return showToast({ type: 'error', title, message, duration })
    }

    const info = (title: string, message?: string, duration?: number) => {
        return showToast({ type: 'info', title, message, duration })
    }

    return {
        toasts,
        showToast,
        removeToast,
        success,
        error,
        info,
    }
}

