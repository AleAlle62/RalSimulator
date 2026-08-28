import { computed, type Ref } from 'vue'

import type { SalaryBreakdown } from '@/domain'
import { formatPercent } from '@/presentation/formatters'

export type StepTone = 'withheld' | 'added'

export interface WaterfallStep {
  id: string
  label: string
  detail: string
  amount: number
  /** Running balance once this step is applied, used to size the bar. */
  remainingAfter: number
  tone: StepTone
}

/**
 * Turns a breakdown into the sequence of moves between gross and net.
 *
 * Order matters and mirrors payroll: contributions come out first because they shrink the
 * base the tax is computed on. Sums that are paid but never taxed are added back at the
 * end, which is why the running balance can go up again.
 */
export function useWaterfallSteps(breakdown: Ref<SalaryBreakdown | null>) {
  return computed<WaterfallStep[]>(() => {
    if (breakdown.value === null) {
      return []
    }

    const { socialContributions, incomeTax, localSurtaxes, grossAnnualSalary } = breakdown.value
    const { reliefs } = incomeTax

    const steps: WaterfallStep[] = []
    let remaining = grossAnnualSalary

    function withhold(id: string, label: string, detail: string, amount: number) {
      remaining -= amount
      steps.push({ id, label, detail, amount, remainingAfter: remaining, tone: 'withheld' })
    }

    function addBack(id: string, label: string, detail: string, amount: number) {
      if (amount <= 0) {
        return
      }
      remaining += amount
      steps.push({ id, label, detail, amount, remainingAfter: remaining, tone: 'added' })
    }

    withhold(
      'contributions',
      'Contributi INPS',
      `${formatPercent(socialContributions.baseRate)} a carico del dipendente`,
      socialContributions.total,
    )

    withhold(
      'income-tax',
      'IRPEF',
      `Lorda ${formatPercent(incomeTax.grossTax / breakdown.value.taxableIncome)} meno le detrazioni`,
      incomeTax.netTax,
    )

    withhold(
      'regional-surtax',
      'Addizionale regionale',
      'Lombardia, a scaglioni',
      localSurtaxes.regional,
    )

    withhold(
      'municipal-surtax',
      'Addizionale comunale',
      localSurtaxes.municipal === 0
        ? 'Milano, sotto la soglia di esenzione'
        : 'Milano, 0,80% sull’intero imponibile',
      localSurtaxes.municipal,
    )

    addBack(
      'wedge-bonus',
      'Bonus taglio del cuneo',
      'Somma esente, pagata in busta',
      reliefs.exemptWedgeCutBonus,
    )

    addBack(
      'supplementary-allowance',
      'Trattamento integrativo',
      'Credito riconosciuto in busta',
      reliefs.supplementaryAllowance,
    )

    return steps
  })
}
