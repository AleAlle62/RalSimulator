import { ref } from 'vue'

export type Theme = 'light' | 'dark'

const STORAGE_KEY = 'ral-simulator-theme'

function readInitialTheme(): Theme {
  return document.documentElement.classList.contains('dark') ? 'dark' : 'light'
}

/**
 * The class is already on <html> before Vue boots, set by an inline script that runs ahead of
 * first paint. This composable only keeps that state in sync from here on.
 */
const theme = ref<Theme>(readInitialTheme())

export function useTheme() {
  function setTheme(next: Theme) {
    theme.value = next
    document.documentElement.classList.toggle('dark', next === 'dark')
    localStorage.setItem(STORAGE_KEY, next)
  }

  function toggleTheme() {
    setTheme(theme.value === 'dark' ? 'light' : 'dark')
  }

  return { theme, toggleTheme }
}
