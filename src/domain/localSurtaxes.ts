import { applyProgressiveBrackets } from './brackets'
import type { LocalSurtaxes } from './types'
import type { TaxYearConfig } from './taxYear2026'

/**
 * The municipal threshold is an exemption, not an allowance: one euro above it the rate
 * applies to the entire taxable income. The resulting step in the net salary curve is a
 * genuine feature of the rules, not a rounding artefact.
 */
function calculateMunicipalSurtax(taxableIncome: number, config: TaxYearConfig): number {
  const { rate, exemptionThreshold } = config.municipalSurtax

  if (taxableIncome <= exemptionThreshold) {
    return 0
  }

  return taxableIncome * rate
}

export function calculateLocalSurtaxes(
  taxableIncome: number,
  config: TaxYearConfig,
): LocalSurtaxes {
  const regional = applyProgressiveBrackets(taxableIncome, config.regionalSurtax.brackets)
  const municipal = calculateMunicipalSurtax(taxableIncome, config)

  return {
    regional,
    municipal,
    total: regional + municipal,
  }
}
