import { computed, type Ref } from 'vue'

import type { SalaryBreakdown } from '@/domain'

export type ShareTone = 'kept' | 'pension' | 'tax' | 'local'

export interface SalaryShare {
  id: string
  label: string
  hint: string
  amount: number
  /** Fraction of the whole, so the segments always add up to 100%. */
  share: number
  tone: ShareTone
}

/**
 * Splits the gross into the few parts a non-expert actually needs: what stays, what funds the
 * pension, and what goes in tax. The running balance of a payroll ledger is left out on purpose;
 * it answers an accountant's question, not "where did my money go".
 */
export function useSalarySplit(breakdown: Ref<SalaryBreakdown | null>) {
  return computed<SalaryShare[]>(() => {
    if (breakdown.value === null) {
      return []
    }

    const { socialContributions, incomeTax, localSurtaxes, netAnnualSalary } = breakdown.value

    const parts: Omit<SalaryShare, 'share'>[] = [
      {
        id: 'kept',
        label: 'Resta a te',
        hint: 'Quello che arriva davvero sul conto',
        amount: netAnnualSalary,
        tone: 'kept',
      },
      {
        id: 'pension',
        label: 'Contributi per la pensione',
        hint: 'Non sono tasse: finanziano la tua pensione futura',
        amount: socialContributions.total,
        tone: 'pension',
      },
      {
        id: 'income-tax',
        label: 'IRPEF',
        hint: 'L’imposta sul reddito, già al netto degli sconti',
        amount: incomeTax.netTax,
        tone: 'tax',
      },
      {
        id: 'local',
        label: 'Regione e Comune',
        hint: 'Le addizionali di Lombardia e Milano',
        amount: localSurtaxes.total,
        tone: 'local',
      },
    ]

    const total = parts.reduce((sum, part) => sum + part.amount, 0)

    return parts.map((part) => ({ ...part, share: part.amount / total }))
  })
}
