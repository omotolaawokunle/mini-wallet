<template>
    <Transition enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="opacity-100 translate-y-0 sm:translate-x-0"
        leave-active-class="transition-all duration-200 ease-in" leave-from-class="opacity-100 translate-y-0 sm:translate-x-0"
        leave-to-class="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2">
        <div v-if="visible"
            class="fixed top-4 right-4 z-50 w-full max-w-sm bg-white rounded-lg shadow-lg border-l-4 overflow-hidden" :class="[
                type === 'success' ? 'border-green-500' : type === 'error' ? 'border-red-500' : 'border-blue-500'
            ]">
            <div class="p-4 flex items-start gap-3">
                <!-- Icon -->
                <div class="flex-shrink-0">
                    <!-- Success Icon -->
                    <svg v-if="type === 'success'" class="w-6 h-6 text-green-500" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <!-- Error Icon -->
                    <svg v-else-if="type === 'error'" class="w-6 h-6 text-red-500" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <!-- Info Icon -->
                    <svg v-else class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                </div>

                <!-- Content -->
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">
                        {{ title }}
                    </p>
                    <p v-if="message" class="mt-1 text-sm text-gray-600">
                        {{ message }}
                    </p>
                </div>

                <!-- Close Button -->
                <button @click="close"
                    class="flex-shrink-0 ml-auto inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:text-gray-600 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <!-- Progress bar -->
            <div class="h-1 bg-gray-100">
                <div class="h-full transition-all duration-100 ease-linear" :style="{ width: `${progress}%` }" :class="[
                    type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'
                ]"></div>
            </div>
        </div>
    </Transition>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'

interface Props {
    type?: 'success' | 'error' | 'info'
    title: string
    message?: string
    duration?: number
    visible: boolean
}

const props = withDefaults(defineProps<Props>(), {
    type: 'info',
    message: '',
    duration: 5000,
    visible: false,
})

const emit = defineEmits<{
    (e: 'close'): void
}>()

const progress = ref(100)
let interval: NodeJS.Timeout | null = null

const startProgress = () => {
    if (interval) clearInterval(interval)

    progress.value = 100
    const step = 100 / (props.duration / 100)

    interval = setInterval(() => {
        progress.value -= step
        if (progress.value <= 0) {
            close()
        }
    }, 100)
}

const close = () => {
    if (interval) {
        clearInterval(interval)
        interval = null
    }
    emit('close')
}

watch(() => props.visible, (newVal) => {
    if (newVal) {
        startProgress()
    } else {
        if (interval) {
            clearInterval(interval)
            interval = null
        }
    }
})

onMounted(() => {
    if (props.visible) {
        startProgress()
    }
})
</script>

