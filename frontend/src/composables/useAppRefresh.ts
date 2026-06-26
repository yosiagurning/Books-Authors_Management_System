import { ref } from 'vue'

const refreshToken = ref(0)

export function useAppRefresh() {
  function notifyDataChanged() {
    refreshToken.value += 1
  }

  return {
    refreshToken,
    notifyDataChanged,
  }
}
