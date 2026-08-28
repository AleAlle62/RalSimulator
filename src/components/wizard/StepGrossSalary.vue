<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
      <label for="gross-salary" class="text-sm font-medium text-ink">
        Retribuzione annua lorda
      </label>
      <p class="max-w-[62ch] text-sm text-ink-muted">
        È la cifra scritta sul contratto, prima di qualsiasi trattenuta. Non comprende i buoni
        pasto né i rimborsi spese.
      </p>
    </div>

    <div
      class="flex items-baseline gap-3 rounded-control border bg-surface px-4 py-3 transition-colors duration-150"
      :class="validationError ? 'border-withheld' : 'border-border focus-within:border-primary'"
    >
      <span class="text-xl text-ink-muted" aria-hidden="true">€</span>
      <input
        id="gross-salary"
        ref="inputRef"
        :value="modelValue"
        class="w-full min-w-0 bg-transparent text-3xl font-semibold tabular text-ink outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:m-0 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:m-0 [&::-webkit-outer-spin-button]:appearance-none"
        type="number"
        inputmode="numeric"
        min="1"
        step="500"
        :aria-describedby="validationError ? 'gross-salary-error' : 'gross-salary-hint'"
        :aria-invalid="validationError ? 'true' : undefined"
        @input="onInput"
        @keydown.enter.prevent="emit('submit')"
      />
    </div>

    <p v-if="validationError" id="gross-salary-error" class="text-sm text-withheld" role="alert">
      {{ validationError }}
    </p>
    <div v-else id="gross-salary-hint" class="flex flex-wrap gap-2">
      <button
        v-for="preset in PRESETS"
        :key="preset"
        type="button"
        class="rounded-full border border-border px-3 py-1 text-xs tabular text-ink-muted transition-colors duration-150 hover:border-primary hover:text-primary"
        @click="emit('update:modelValue', preset)"
      >
        {{ formatEuro(preset) }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, useTemplateRef } from 'vue'

import { formatEuro } from '@/presentation/formatters'

defineProps<{
  modelValue: number
  validationError: string | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number]
  submit: []
}>()

const PRESETS = [25_000, 35_000, 50_000, 70_000]

const inputRef = useTemplateRef<HTMLInputElement>('inputRef')

onMounted(() => {
  inputRef.value?.focus()
})

function onInput(event: Event) {
  const { value } = event.target as HTMLInputElement
  emit('update:modelValue', value === '' ? Number.NaN : Number(value))
}
</script>
