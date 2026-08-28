import { computed, ref } from 'vue'

export interface WizardStep {
  id: string
  title: string
  question: string
}

/**
 * Linear step navigation. Moving forward is gated by the caller, so a step can refuse to
 * advance while its input is invalid; moving back to an already visited step is always allowed.
 */
export function useWizard(steps: WizardStep[]) {
  const currentIndex = ref(0)
  const furthestIndex = ref(0)
  /** Which way the last move went, so the transition can follow it. */
  const direction = ref<'forward' | 'backward'>('forward')

  const currentStep = computed(() => steps[currentIndex.value] as WizardStep)
  const isFirstStep = computed(() => currentIndex.value === 0)
  const isLastStep = computed(() => currentIndex.value === steps.length - 1)

  function goTo(index: number) {
    if (index < 0 || index >= steps.length || index > furthestIndex.value) {
      return
    }

    direction.value = index > currentIndex.value ? 'forward' : 'backward'
    currentIndex.value = index
  }

  function goNext() {
    if (isLastStep.value) {
      return
    }

    direction.value = 'forward'
    currentIndex.value += 1
    furthestIndex.value = Math.max(furthestIndex.value, currentIndex.value)
  }

  function goBack() {
    if (isFirstStep.value) {
      return
    }

    direction.value = 'backward'
    currentIndex.value -= 1
  }

  function reset() {
    direction.value = 'backward'
    currentIndex.value = 0
    furthestIndex.value = 0
  }

  return {
    steps,
    currentIndex,
    furthestIndex,
    currentStep,
    direction,
    isFirstStep,
    isLastStep,
    goTo,
    goNext,
    goBack,
    reset,
  }
}
