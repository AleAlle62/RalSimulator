<template>
  <ol class="flex flex-col">
    <li
      v-for="(step, index) in steps"
      :key="step.id"
      class="flex flex-col gap-2 border-b border-border py-3.5"
    >
      <div class="flex items-baseline justify-between gap-4">
        <span class="text-sm font-medium text-ink">{{ step.label }}</span>
        <span
          class="shrink-0 text-sm font-medium tabular"
          :class="step.tone === 'withheld' ? 'text-withheld' : 'text-kept'"
        >
          {{ step.tone === 'withheld' ? '−' : '+' }} {{ formatEuro(step.amount) }}
        </span>
      </div>

      <p class="text-xs text-ink-muted">{{ step.detail }}</p>

      <div class="flex h-1.5 overflow-hidden rounded-full bg-surface" role="presentation">
        <span
          class="h-full bg-border transition-[width] duration-500 ease-out-quart"
          :style="barStyle(unchangedPortion(step), index)"
        />
        <span
          class="h-full transition-[width] duration-500 ease-out-quart"
          :class="step.tone === 'withheld' ? 'bg-withheld' : 'bg-kept'"
          :style="barStyle(step.amount, index)"
        />
      </div>

      <span class="self-end text-xs tabular text-ink-muted">
        resta {{ formatEuro(step.remainingAfter) }}
      </span>
    </li>
  </ol>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'

import type { WaterfallStep } from '@/composables/useWaterfallSteps'
import { formatEuro } from '@/presentation/formatters'

const props = defineProps<{
  steps: WaterfallStep[]
  grossAnnualSalary: number
}>()

/**
 * Bars start collapsed and grow once mounted. The figures next to them are already correct at
 * that point, so nothing readable depends on the animation running.
 */
const isRevealed = ref(false)

onMounted(() => {
  requestAnimationFrame(() => {
    isRevealed.value = true
  })
})

/** The portion of the bar that this step leaves untouched, shared by both tones. */
function unchangedPortion(step: WaterfallStep): number {
  const remainingBefore =
    step.tone === 'withheld' ? step.remainingAfter + step.amount : step.remainingAfter - step.amount

  return Math.min(step.remainingAfter, remainingBefore)
}

function barStyle(amount: number, index: number) {
  return {
    width: isRevealed.value ? `${(amount / props.grossAnnualSalary) * 100}%` : '0%',
    transitionDelay: `${index * 70}ms`,
  }
}
</script>
