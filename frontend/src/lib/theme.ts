import { ref, watch } from 'vue'

export type ThemeMode = 'light' | 'dark'
const STORAGE_KEY = 'ba-theme'

function detectInitial(): ThemeMode {
  if (typeof window === 'undefined') return 'light'
  const saved = window.localStorage.getItem(STORAGE_KEY) as ThemeMode | null
  if (saved === 'light' || saved === 'dark') return saved
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

const theme = ref<ThemeMode>(detectInitial())

function apply(mode: ThemeMode) {
  if (typeof document === 'undefined') return
  document.documentElement.dataset.theme = mode
  document.documentElement.classList.toggle('dark', mode === 'dark')
}

apply(theme.value)

watch(theme, (mode) => {
  apply(mode)
  try {
    window.localStorage.setItem(STORAGE_KEY, mode)
  } catch (_) {
    // ignore
  }
})

export function useTheme() {
  return {
    theme,
    setTheme: (mode: ThemeMode) => {
      theme.value = mode
    },
    toggleTheme: () => {
      theme.value = theme.value === 'dark' ? 'light' : 'dark'
    },
  }
}
