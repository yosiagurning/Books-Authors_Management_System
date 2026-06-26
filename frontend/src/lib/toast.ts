import { reactive } from 'vue'

export type ToastVariant = 'success' | 'error' | 'info'

export interface Toast {
  id: number
  title: string
  description?: string
  variant: ToastVariant
  duration: number
}

const state = reactive<{ toasts: Toast[] }>({ toasts: [] })

let counter = 0

function dismiss(id: number) {
  const idx = state.toasts.findIndex((t) => t.id === id)
  if (idx !== -1) state.toasts.splice(idx, 1)
}

function push(
  variant: ToastVariant,
  title: string,
  description?: string,
  duration = 4500,
) {
  counter += 1
  const id = counter
  const toast: Toast = { id, title, description, variant, duration }
  state.toasts.push(toast)
  if (duration > 0) {
    window.setTimeout(() => dismiss(id), duration)
  }
  return id
}

export function useToast() {
  return {
    toasts: state.toasts,
    dismiss,
    success: (title: string, description?: string, duration?: number) =>
      push('success', title, description, duration),
    error: (title: string, description?: string, duration?: number) =>
      push('error', title, description, duration),
    info: (title: string, description?: string, duration?: number) =>
      push('info', title, description, duration),
  }
}
