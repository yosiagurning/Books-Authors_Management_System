<script setup lang="ts">
import { useToast } from '@/lib/toast'
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-vue-next'

const { toasts, dismiss } = useToast()

const iconFor = (variant: string) => {
  if (variant === 'success') return CheckCircle2
  if (variant === 'error') return AlertCircle
  if (variant === 'warning') return AlertTriangle
  return Info
}
</script>

<template>
  <Teleport to="body">
    <div class="toast-container" aria-live="polite" aria-atomic="true">
      <transition-group name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          :class="['toast', `is-${toast.variant}`]"
          role="status"
        >
          <span class="toast-icon">
            <component :is="iconFor(toast.variant)" :size="16" :stroke-width="2" />
          </span>
          <div class="toast-copy">
            <strong>{{ toast.title }}</strong>
            <p v-if="toast.description">{{ toast.description }}</p>
          </div>
          <button class="toast-close" type="button" aria-label="Tutup" @click="dismiss(toast.id)">
            <X :size="14" :stroke-width="2" />
          </button>
        </div>
      </transition-group>
    </div>
  </Teleport>
</template>
