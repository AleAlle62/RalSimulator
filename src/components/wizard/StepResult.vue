<template>
  <div class="flex flex-col gap-8">
    <div class="flex flex-col gap-4 rounded-panel bg-surface p-5">
      <div class="flex items-baseline justify-between gap-4">
        <span class="text-sm text-ink-muted">Retribuzione annua lorda</span>
        <span class="text-sm font-medium tabular text-ink">
          {{ formatEuro(breakdown.grossAnnualSalary) }}
        </span>
      </div>

      <div class="flex flex-col gap-1 border-t border-border pt-4">
        <span class="text-sm text-ink-muted">Netto annuo</span>
        <span class="text-4xl font-semibold tabular text-kept">
          <CountUp
            :key="`net-${revision}`"
            :to="breakdown.netAnnualSalary"
            :duration="1.2"
            separator="."
          />
          <span aria-hidden="true"> €</span>
        </span>
        <span class="text-xs text-ink-muted">
          Trattenuto il {{ formatPercent(breakdown.effectiveWithholdingRate) }} del lordo, cioè
          {{ formatEuro(breakdown.totalWithholdings) }}
        </span>
      </div>
    </div>

    <section class="flex flex-col gap-3">
      <h3 class="text-sm font-medium text-ink">Dove finisce il lordo</h3>
      <WithholdingLedger :steps="steps" :gross-annual-salary="breakdown.grossAnnualSalary" />
    </section>

    <PayslipGrid :schedule="breakdown.payslips" />

    <section class="flex flex-col gap-3">
      <h3 class="text-sm font-medium text-ink">Su cosa si basa questo calcolo</h3>
      <dl class="grid gap-x-6 gap-y-0 sm:grid-cols-2">
        <div
          v-for="assumption in assumptions"
          :key="assumption.label"
          class="flex justify-between gap-3 border-b border-border py-2"
        >
          <dt class="text-xs text-ink-muted">{{ assumption.label }}</dt>
          <dd class="text-right text-xs text-ink">{{ assumption.value }}</dd>
        </div>
      </dl>
      <p class="max-w-[68ch] text-xs text-ink-muted">
        Prototipo dimostrativo per il caso standard di un impiegato: non sostituisce il conteggio
        di un consulente del lavoro. Fonti e semplificazioni sono nel README del progetto.
      </p>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed, toRef } from 'vue'

import PayslipGrid from '../result/PayslipGrid.vue'
import WithholdingLedger from '../result/WithholdingLedger.vue'
import CountUp from '../vendor/CountUp.vue'
import { useWaterfallSteps } from '@/composables/useWaterfallSteps'
import { TAX_YEAR_2026, type SalaryBreakdown } from '@/domain'
import { formatEuro, formatPercent } from '@/presentation/formatters'
import { SECTOR_LABELS } from '@/presentation/payslipLabels'

const props = defineProps<{
  breakdown: SalaryBreakdown
  revision: number
}>()

const steps = useWaterfallSteps(toRef(props, 'breakdown'))

const assumptions = computed(() => [
  { label: 'Anno d’imposta', value: String(TAX_YEAR_2026.year) },
  { label: 'Settore', value: SECTOR_LABELS[props.breakdown.input.sector] },
  { label: 'Inquadramento', value: 'Impiegato a tempo indeterminato, anno intero' },
  {
    label: 'Residenza',
    value: `${TAX_YEAR_2026.municipalSurtax.municipality}, ${TAX_YEAR_2026.regionalSurtax.region}`,
  },
  { label: 'Familiari a carico', value: 'Nessuno' },
  { label: 'Agevolazioni', value: 'Nessuna' },
])
</script>
