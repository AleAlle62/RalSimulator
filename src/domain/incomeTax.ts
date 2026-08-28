import { applyProgressiveBrackets } from './brackets'
import { calculateTaxReliefs } from './taxReliefs'
import type { IncomeTax } from './types'
import type { TaxYearConfig } from './taxYear2026'

export function calculateIncomeTax(taxableIncome: number, config: TaxYearConfig): IncomeTax {
  const grossTax = applyProgressiveBrackets(taxableIncome, config.incomeTax.brackets)
  const reliefs = calculateTaxReliefs(taxableIncome, grossTax, config)

  return {
    grossTax,
    reliefs,
    netTax: Math.max(0, grossTax - reliefs.totalDeductedFromTax),
  }
}

/**
 * Rate of the highest bracket the income reaches. Payroll withholds extra monthly payments
 * at this rate, since reliefs are already spread across the twelve ordinary payslips.
 */
export function findMarginalRate(taxableIncome: number, config: TaxYearConfig): number {
  const bracket = config.incomeTax.brackets.find(
    (candidate) => candidate.upTo === null || taxableIncome <= candidate.upTo,
  )

  return bracket?.rate ?? 0
}
