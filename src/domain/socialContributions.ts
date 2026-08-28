import type { SocialContributions, Sector } from './types'
import type { TaxYearConfig } from './taxYear2026'

/**
 * Employee-side INPS contributions. Two effects stack on top of the base rate:
 * the annual ceiling caps the contributory base, and income above the first pension
 * band carries an extra point.
 */
export function calculateSocialContributions(
  grossAnnualSalary: number,
  sector: Sector,
  config: TaxYearConfig,
): SocialContributions {
  const { employeeRateBySector, additionalRateThreshold, additionalRate, annualCeiling } =
    config.socialContributions

  const contributoryBase = Math.min(grossAnnualSalary, annualCeiling)
  const baseRate = employeeRateBySector[sector]
  const baseAmount = contributoryBase * baseRate

  const amountAboveThreshold = Math.max(0, contributoryBase - additionalRateThreshold)
  const additionalRateAmount = amountAboveThreshold * additionalRate

  return {
    contributoryBase,
    baseRate,
    baseAmount,
    additionalRateAmount,
    total: baseAmount + additionalRateAmount,
  }
}
