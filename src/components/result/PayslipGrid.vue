<template>
  <section class="flex flex-col gap-3">
    <h3 class="text-sm font-medium text-ink">Quanto entra in busta</h3>

    <ul class="grid gap-2 sm:grid-cols-[repeat(auto-fit,minmax(160px,1fr))]">
      <li
        v-for="payslip in payslips"
        :key="payslip.kind"
        class="flex flex-col gap-1 rounded-panel border border-border p-4"
      >
        <span class="text-xs text-ink-muted">
          {{ PAYSLIP_LABELS[payslip.kind] }} {{ PAYSLIP_OCCURRENCES[payslip.kind] }}
        </span>
        <span class="text-xl font-semibold tabular text-kept">
          {{ formatEuroWithCents(payslip.net) }}
        </span>
        <span class="text-xs tabular text-ink-muted">
          su {{ formatEuro(payslip.gross) }} lordi
        </span>
      </li>
    </ul>

    <p v-if="hasExtras" class="max-w-[68ch] text-xs text-ink-muted">
      Tredicesima e quattordicesima hanno lo stesso lordo di una mensilità normale, ma arrivano più
      basse: gli sconti fiscali sono già stati usati tutti sulle dodici buste ordinarie.
    </p>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import type { PayslipSchedule } from '@/domain'
import { formatEuro, formatEuroWithCents } from '@/presentation/formatters'
import { PAYSLIP_LABELS, PAYSLIP_OCCURRENCES } from '@/presentation/payslipLabels'

const props = defineProps<{ schedule: PayslipSchedule }>()

const payslips = computed(() => [props.schedule.ordinary, ...props.schedule.extras])
const hasExtras = computed(() => props.schedule.extras.length > 0)
</script>
