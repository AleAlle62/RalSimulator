<template>
  <nav class="flex items-stretch gap-2" aria-label="Avanzamento">
    <button
      v-for="(step, index) in steps"
      :key="step.id"
      type="button"
      class="group flex flex-1 flex-col gap-2 text-left"
      :disabled="index > furthestIndex"
      :aria-current="index === currentIndex ? 'step' : undefined"
      :aria-label="`Passo ${index + 1} di ${steps.length}: ${step.title}`"
      @click="emit('navigate', index)"
    >
      <span
        class="h-1 w-full rounded-full transition-colors duration-200"
        :class="index <= currentIndex ? 'bg-primary' : 'bg-border'"
      />
      <span class="flex items-baseline gap-1.5">
        <span
          class="text-xs font-semibold tabular transition-colors duration-200"
          :class="index <= currentIndex ? 'text-primary' : 'text-ink-muted'"
        >
          {{ index + 1 }}
        </span>
        <span
          class="text-xs transition-colors duration-200 group-enabled:group-hover:text-ink"
          :class="index === currentIndex ? 'font-medium text-ink' : 'text-ink-muted'"
        >
          {{ step.title }}
        </span>
      </span>
    </button>
  </nav>
</template>

<script setup lang="ts">
import type { WizardStep } from '@/composables/useWizard'

defineProps<{
  steps: WizardStep[]
  currentIndex: number
  furthestIndex: number
}>()

const emit = defineEmits<{ navigate: [index: number] }>()
</script>
