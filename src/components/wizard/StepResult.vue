<template>
  <div class="flex flex-col gap-10">
    <div class="flex flex-col items-center gap-2 rounded-panel bg-surface px-5 py-7 text-center">
      <span class="text-sm text-ink-muted">In un anno ti restano</span>

      <span class="flex items-center gap-1.5 font-semibold text-kept">
        <Counter
          :key="`net-${revision}`"
          :value="Math.round(breakdown.netAnnualSalary)"
          :places="thousandsPlaces(breakdown.netAnnualSalary)"
          :font-size="44"
          :gap="1"
          :horizontal-padding="0"
          :gradient-height="10"
          gradient-from="var(--color-surface)"
          gradient-to="transparent"
          text-color="var(--color-kept)"
          font-weight="600"
        />
        <span class="text-3xl" aria-hidden="true">€</span>
      </span>

      <p class="text-sm text-ink-muted">
        Su {{ formatEuro(breakdown.grossAnnualSalary) }} lordi, cioè
        {{ formatEuro(breakdown.payslips.ordinary.net) }} in busta ogni mese
      </p>
    </div>

    <SalarySplitBar :shares="shares" :gross-annual-salary="breakdown.grossAnnualSalary" />

    <PayslipGrid :schedule="breakdown.payslips" />

    <details class="group rounded-panel border border-border">
      <summary
        class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-medium text-ink"
      >
        Cosa abbiamo dato per scontato
        <svg
          class="h-4 w-4 shrink-0 text-ink-muted transition-transform duration-150 group-open:rotate-180"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="m6 9 6 6 6-6" />
        </svg>
      </summary>

      <div class="flex flex-col gap-3 border-t border-border px-4 py-4">
        <ul class="flex flex-col gap-1.5">
          <li v-for="assumption in assumptions" :key="assumption" class="flex gap-2 text-xs text-ink-muted">
            <span class="text-ink-muted" aria-hidden="true">·</span>
            <span>{{ assumption }}</span>
          </li>
        </ul>
        <p class="text-xs text-ink-muted">
          È un prototipo dimostrativo, non sostituisce il conteggio di un consulente del lavoro.
        </p>
      </div>
    </details>
  </div>
</template>

<script setup lang="ts">
import { computed, toRef } from 'vue'

import PayslipGrid from '../result/PayslipGrid.vue'
import SalarySplitBar from '../result/SalarySplitBar.vue'
import Counter from '../vendor/Counter.vue'
import { useSalarySplit } from '@/composables/useSalarySplit'
import { TAX_YEAR_2026, type SalaryBreakdown } from '@/domain'
import { formatEuro } from '@/presentation/formatters'
import { SECTOR_LABELS } from '@/presentation/payslipLabels'

const props = defineProps<{
  breakdown: SalaryBreakdown
  revision: number
}>()

const shares = useSalarySplit(toRef(props, 'breakdown'))

/** Counter renders one column per place, so the thousands dot has to be passed in explicitly. */
function thousandsPlaces(value: number): (number | '.')[] {
  const digitCount = Math.max(1, Math.round(Math.abs(value))).toString().length
  const places: (number | '.')[] = []

  for (let exponent = digitCount - 1; exponent >= 0; exponent -= 1) {
    places.push(10 ** exponent)
    if (exponent > 0 && exponent % 3 === 0) {
      places.push('.')
    }
  }

  return places
}

const assumptions = computed(() => [
  `Anno d’imposta ${TAX_YEAR_2026.year}, impiegato a tempo indeterminato per l’anno intero`,
  `Contratto del settore ${SECTOR_LABELS[props.breakdown.input.sector].toLowerCase()}`,
  `Residenza a ${TAX_YEAR_2026.municipalSurtax.municipality}, in ${TAX_YEAR_2026.regionalSurtax.region}`,
  'Nessun familiare a carico',
  'Nessuna agevolazione e nessun fondo pensione',
])
</script>
