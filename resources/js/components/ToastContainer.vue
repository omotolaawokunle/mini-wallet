<template>
    <div class="fixed top-4 right-4 z-50 space-y-3 pointer-events-none">
        <Toast
            v-for="toast in toasts"
            :key="toast.id"
            :type="toast.type"
            :title="toast.title"
            :message="toast.message"
            :duration="toast.duration"
            :visible="true"
            @close="removeToast(toast.id)"
            class="pointer-events-auto"
        />
    </div>
</template>

<script setup lang="ts">
import { useToast } from '@/composables/useToast'
import Toast from './Toast.vue'

const { toasts, removeToast } = useToast()

</script>

