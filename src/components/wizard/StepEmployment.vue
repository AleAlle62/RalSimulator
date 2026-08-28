<template>
  <div class="flex flex-col gap-8">
    <fieldset class="flex flex-col gap-3">
      <legend class="text-sm font-medium text-ink">In quante mensilità è diviso lo stipendio</legend>
      <p class="max-w-[62ch] text-sm text-ink-muted">
        Con 13 o 14 mensilità il lordo annuo non cambia: viene distribuito su più buste, e quelle
        in più arrivano più basse. Lo vedrai nel risultato.
      </p>

      <div class="grid grid-cols-3 gap-2">
        <label
          v-for="count in MONTHLY_PAYMENT_OPTIONS"
          :key="count"
          class="relative flex cursor-pointer flex-col items-center gap-0.5 rounded-control border px-3 py-3 transition-colors duration-150"
          :class="
            monthlyPaymentsCount === count
              ? 'border-primary bg-primary text-primary-contrast'
              : 'border-border bg-surface text-ink hover:border-primary'
          "
        >
          <input
            class="absolute inset-0 cursor-pointer opacity-0"
            type="radio"
            name="monthly-payments"
            :value="count"
            :checked="monthlyPaymentsCount === count"
            @change="emit('update:monthlyPaymentsCount', count)"
          />
          <span class="text-lg font-semibold tabular">{{ count }}</span>
          <span
            class="text-xs"
            :class="monthlyPaymentsCount === count ? 'opacity-80' : 'text-ink-muted'"
          >
            {{ MONTHLY_PAYMENT_NOTES[count] }}
          </span>
        </label>
      </div>
    </fieldset>

    <fieldset class="flex flex-col gap-3">
      <legend class="text-sm font-medium text-ink">In che settore lavori</legend>
      <p class="max-w-[62ch] text-sm text-ink-muted">
        Cambia l’aliquota dei contributi: l’industria versa anche lo 0,30% di CIGS, che il
        commercio non paga.
      </p>

      <div class="flex flex-col gap-2">
        <label
          v-for="option in SECTOR_OPTIONS"
          :key="option"
          class="relative flex cursor-pointer items-center gap-3 rounded-control border bg-surface px-4 py-3 transition-colors duration-150"
          :class="sector === option ? 'border-primary' : 'border-border hover:border-primary'"
        >
          <input
            class="absolute inset-0 cursor-pointer opacity-0"
            type="radio"
            name="sector"
            :value="option"
            :checked="sector === option"
            @change="emit('update:sector', option)"
          />
          <span
            class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full border transition-colors duration-150"
            :class="sector === option ? 'border-primary' : 'border-border'"
            aria-hidden="true"
          >
            <span v-if="sector === option" class="h-2 w-2 rounded-full bg-primary" />
          </span>
          <span class="flex flex-1 flex-col">
            <span class="text-sm font-medium text-ink">{{ SECTOR_LABELS[option] }}</span>
            <span class="text-xs text-ink-muted">{{ SECTOR_DESCRIPTIONS[option] }}</span>
          </span>
          <span class="text-sm tabular text-ink-muted">
            {{ formatPercent(CONTRIBUTION_RATES[option]) }}
          </span>
        </label>
      </div>
    </fieldset>
  </div>
</template>

<script setup lang="ts">
import { TAX_YEAR_2026, type MonthlyPaymentsCount, type Sector } from '@/domain'
import { formatPercent } from '@/presentation/formatters'
import { SECTOR_DESCRIPTIONS, SECTOR_LABELS } from '@/presentation/payslipLabels'

defineProps<{
  monthlyPaymentsCount: MonthlyPaymentsCount
  sector: Sector
}>()

const emit = defineEmits<{
  'update:monthlyPaymentsCount': [value: MonthlyPaymentsCount]
  'update:sector': [value: Sector]
}>()

const MONTHLY_PAYMENT_OPTIONS: MonthlyPaymentsCount[] = [12, 13, 14]

const MONTHLY_PAYMENT_NOTES: Record<MonthlyPaymentsCount, string> = {
  12: 'nessuna extra',
  13: 'con tredicesima',
  14: 'più quattordicesima',
}

const SECTOR_OPTIONS: Sector[] = ['commerce', 'industry']

const CONTRIBUTION_RATES = TAX_YEAR_2026.socialContributions.employeeRateBySector
</script>
