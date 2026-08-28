<template>
  <section class="flex flex-col gap-3">
    <div class="flex flex-col gap-1">
      <h3 class="text-sm font-medium text-ink">Quanto entra ogni mese</h3>
      <p v-if="hasExtras" class="max-w-[68ch] text-xs text-ink-muted">
        Le buste extra hanno lo stesso lordo di una ordinaria ma arrivano più basse: detrazioni e
        addizionali sono già state assorbite dalle dodici mensilità, quindi qui resta l’IRPEF piena.
      </p>
    </div>

    <ul class="grid gap-2 sm:grid-cols-[repeat(auto-fit,minmax(180px,1fr))]">
      <li
        v-for="payslip in payslips"
        :key="payslip.kind"
        class="flex flex-col gap-3 rounded-panel border border-border p-4"
      >
        <div class="flex items-baseline justify-between gap-2">
          <span class="text-xs font-medium text-ink">{{ PAYSLIP_LABELS[payslip.kind] }}</span>
          <span class="text-xs tabular text-ink-muted">{{ PAYSLIP_OCCURRENCES[payslip.kind] }}</span>
        </div>

        <span class="text-xl font-semibold tabular text-kept">
          {{ formatEuroWithCents(payslip.net) }}
        </span>

        <dl class="flex flex-col gap-1 border-t border-border pt-3 text-xs">
          <div v-for="line in linesFor(payslip)" :key="line.label" class="flex justify-between gap-3">
            <dt class="text-ink-muted">{{ line.label }}</dt>
            <dd class="tabular text-ink-muted">{{ line.value }}</dd>
          </div>
        </dl>
      </li>
    </ul>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue'

import type { Payslip, PayslipSchedule } from '@/domain'
import { formatEuroWithCents } from '@/presentation/formatters'
import { PAYSLIP_LABELS, PAYSLIP_OCCURRENCES } from '@/presentation/payslipLabels'

const props = defineProps<{ schedule: PayslipSchedule }>()

const payslips = computed(() => [props.schedule.ordinary, ...props.schedule.extras])
const hasExtras = computed(() => props.schedule.extras.length > 0)

function linesFor(payslip: Payslip) {
  return [
    { label: 'Lordo', value: formatEuroWithCents(payslip.gross) },
    { label: 'Contributi', value: `− ${formatEuroWithCents(payslip.socialContributions)}` },
    { label: 'IRPEF', value: `− ${formatEuroWithCents(payslip.incomeTax)}` },
    { label: 'Addizionali', value: `− ${formatEuroWithCents(payslip.localSurtaxes)}` },
  ]
}
</script>
