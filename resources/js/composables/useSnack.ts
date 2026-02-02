import { ref } from 'vue'

export interface SnackOptions {
  message: string
  type?: 'success' | 'error' | 'info' | 'warning'
  timeout?: number
  action?: {
    text: string
    href?: string
    onClick?: () => void
  }
}

const snack = ref<SnackOptions | null>(null)
const visible = ref(false)

export function useSnack() {
  function show(options: SnackOptions | string) {
    snack.value = typeof options === 'string' 
      ? { message: options, type: 'info' } 
      : options
    visible.value = true
  }

  function success(message: string, action?: SnackOptions['action']) {
    show({ message, type: 'success', action, timeout: 4000 })
  }

  function error(message: string) {
    show({ message, type: 'error', timeout: 5000 })
  }

  function hide() {
    visible.value = false
  }

  return {
    snack,
    visible,
    show,
    success,
    error,
    hide,
  }
}