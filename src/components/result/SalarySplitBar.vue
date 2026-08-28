<template>
  <section class="flex flex-col gap-4">
    <h3 class="text-sm font-medium text-ink">
      Come si dividono i tuoi {{ formatEuro(grossAnnualSalary) }} lordi
    </h3>

    <div class="flex h-8 gap-0.5 overflow-hidden rounded-control" role="presentation">
      <span
        v-for="(share, index) in shares"
        :key="share.id"
        class="h-full transition-[width] duration-500 ease-out-quart"
        :class="TONE_BACKGROUNDS[share.tone]"
        :style="{
          width: isRevealed ? `${share.share * 100}%` : '0%',
          transitionDelay: `${index * 80}ms`,
        }"
      />
    </div>

    <ul class="flex flex-col">
      <li
        v-for="share in shares"
        :key="share.id"
        class="flex items-start gap-3 border-b border-border py-3 last:border-b-0"
      >
        <span
          class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full"
          :class="TONE_BACKGROUNDS[share.tone]"
          aria-hidden="true"
        />

        <span class="flex min-w-0 flex-1 flex-col">
          <span class="text-sm font-medium text-ink">{{ share.label }}</span>
          <span class="text-xs text-ink-muted">{{ share.hint }}</span>
        </span>

        <span class="flex shrink-0 flex-col items-end">
          <span class="text-sm font-semibold tabular text-ink">{{ formatEuro(share.amount) }}</span>
          <span class="text-xs tabular text-ink-muted">{{ formatShare(share.share) }}</span>
        </span>
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'

import type { SalaryShare, ShareTone } from '@/composables/useSalarySplit'
import { formatEuro } from '@/presentation/formatters'

defineProps<{
  shares: SalaryShare[]
  grossAnnualSalary: number
}>()

const TONE_BACKGROUNDS: Record<ShareTone, string> = {
  kept: 'bg-share-kept',
  pension: 'bg-share-pension',
  tax: 'bg-share-tax',
  local: 'bg-share-local',
}

const shareFormatter = new Intl.NumberFormat('it-IT', {
  style: 'percent',
  maximumFractionDigits: 0,
})

function formatShare(value: number): string {
  return shareFormatter.format(value)
}

/** Segments grow from zero once mounted; the figures beside them are correct from the start. */
const isRevealed = ref(false)

onMounted(() => {
  requestAnimationFrame(() => {
    isRevealed.value = true
  })
})
</script>
