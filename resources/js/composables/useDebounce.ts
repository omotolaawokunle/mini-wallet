import { ref, watch } from 'vue'

export function useDebounce<T>(value: T, delay: number = 500) {
    const debouncedValue = ref<T>(value)
    let timeout: NodeJS.Timeout | null = null

    watch(() => value, (newValue) => {
        if (timeout) clearTimeout(timeout)

        timeout = setTimeout(() => {
            debouncedValue.value = newValue as any
        }, delay)
    })

    return debouncedValue
}

export function useDebouncedFunction<T extends (...args: any[]) => any>(
    func: T,
    delay: number = 500
) {
    let timeout: NodeJS.Timeout | null = null

    return (...args: Parameters<T>) => {
        if (timeout) clearTimeout(timeout)

        timeout = setTimeout(() => {
            func(...args)
        }, delay)
    }
}

