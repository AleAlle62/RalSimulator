<template>
  <div class="flex flex-col gap-8">
    <WizardProgress
      :steps="steps"
      :current-index="currentIndex"
      :furthest-index="furthestIndex"
      @navigate="goTo"
    />

    <div class="flex flex-col gap-2">
      <h2 class="text-xl font-semibold tracking-[-0.01em] text-ink text-balance">
        {{ currentStep.question }}
      </h2>
    </div>

    <Transition :name="`slide-${direction}`" mode="out-in">
      <div :key="currentStep.id">
        <StepGrossSalary
          v-if="currentStep.id === 'gross'"
          v-model="form.grossAnnualSalary"
          :validation-error="validationError"
          @submit="advance"
        />

        <StepEmployment
          v-else-if="currentStep.id === 'employment'"
          v-model:monthly-payments-count="form.monthlyPaymentsCount"
          v-model:sector="form.sector"
        />

        <StepResult v-else-if="result" :breakdown="result" :revision="revision" />
      </div>
    </Transition>

    <div class="flex items-center justify-between gap-3 border-t border-border pt-6">
      <button
        v-if="!isFirstStep"
        type="button"
        class="rounded-control px-3 py-2.5 text-sm font-medium text-ink-muted transition-colors duration-150 hover:text-ink"
        @click="goBack"
      >
        Indietro
      </button>
      <span v-else />

      <button
        v-if="!isLastStep"
        type="button"
        class="rounded-control bg-primary px-5 py-2.5 text-sm font-semibold text-primary-contrast transition-colors duration-150 hover:bg-primary-hover"
        @click="advance"
      >
        {{ isSecondToLastStep ? 'Calcola' : 'Continua' }}
      </button>
      <button
        v-else
        type="button"
        class="rounded-control border border-border px-5 py-2.5 text-sm font-medium text-ink transition-colors duration-150 hover:border-primary hover:text-primary"
        @click="startOver"
      >
        Ricomincia
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import StepEmployment from './StepEmployment.vue'
import StepGrossSalary from './StepGrossSalary.vue'
import StepResult from './StepResult.vue'
import WizardProgress from './WizardProgress.vue'
import { useSalaryCalculation } from '@/composables/useSalaryCalculation'
import { useWizard, type WizardStep } from '@/composables/useWizard'

const WIZARD_STEPS: WizardStep[] = [
  { id: 'gross', title: 'Lordo', question: 'Quanto guadagni lordo in un anno?' },
  { id: 'employment', title: 'Contratto', question: 'Com’è fatto il tuo contratto?' },
  { id: 'result', title: 'Risultato', question: 'Ecco il tuo stipendio' },
]

const { form, result, validationError, revision, calculate } = useSalaryCalculation()

const {
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
} = useWizard(WIZARD_STEPS)

const isSecondToLastStep = computed(() => currentIndex.value === steps.length - 2)

/**
 * The gross salary is validated before leaving its own step, so the user is corrected where
 * the mistake was made rather than on the results screen.
 */
function advance() {
  if (currentStep.value.id === 'gross') {
    calculate()
    if (validationError.value !== null) {
      return
    }
  }

  if (isSecondToLastStep.value) {
    calculate()
  }

  goNext()
}

function startOver() {
  reset()
}
</script>

<style scoped>
.slide-forward-enter-active,
.slide-forward-leave-active,
.slide-backward-enter-active,
.slide-backward-leave-active {
  transition:
    opacity 180ms var(--ease-out-quart),
    transform 180ms var(--ease-out-quart);
}

.slide-forward-enter-from {
  opacity: 0;
  transform: translateX(12px);
}

.slide-forward-leave-to {
  opacity: 0;
  transform: translateX(-12px);
}

.slide-backward-enter-from {
  opacity: 0;
  transform: translateX(-12px);
}

.slide-backward-leave-to {
  opacity: 0;
  transform: translateX(12px);
}

@media (prefers-reduced-motion: reduce) {
  .slide-forward-enter-from,
  .slide-forward-leave-to,
  .slide-backward-enter-from,
  .slide-backward-leave-to {
    transform: none;
  }
}
</style>
