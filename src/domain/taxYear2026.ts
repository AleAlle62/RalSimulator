import type { ProgressiveBracket, Sector } from './types'

/**
 * Every figure in this file is a legal parameter for tax year 2026, with its source.
 * Nothing here is inferred or interpolated: if a value could not be confirmed against
 * a primary source, it is flagged in the README under "Limiti noti".
 *
 * Keeping the whole fiscal configuration in one declarative module means updating the
 * simulator for 2027 is a data change, not a code change.
 */

export interface TaxYearConfig {
  year: number
  socialContributions: SocialContributionsConfig
  incomeTax: IncomeTaxConfig
  employmentRelief: EmploymentReliefConfig
  wedgeCut: WedgeCutConfig
  supplementaryAllowance: SupplementaryAllowanceConfig
  regionalSurtax: RegionalSurtaxConfig
  municipalSurtax: MunicipalSurtaxConfig
}

interface SocialContributionsConfig {
  /** Employee-side rate per sector. The gap is the 0.30% CIGS contribution. */
  employeeRateBySector: Record<Sector, number>
  /** Income above this threshold carries an extra IVS point. */
  additionalRateThreshold: number
  additionalRate: number
  /** Annual ceiling of the contributory base. */
  annualCeiling: number
}

interface IncomeTaxConfig {
  brackets: ProgressiveBracket[]
}

interface EmploymentReliefConfig {
  flatAmountUpTo: number
  flatAmount: number
  firstTaperUpTo: number
  firstTaperBase: number
  firstTaperVariable: number
  secondTaperUpTo: number
  secondTaperBase: number
}

interface WedgeCutConfig {
  /** Below this income the benefit is a tax-exempt sum, not a tax relief. */
  exemptBonusUpTo: number
  exemptBonusBrackets: ProgressiveBracket[]
  reliefFlatUpTo: number
  reliefFlatAmount: number
  reliefTaperUpTo: number
}

interface SupplementaryAllowanceConfig {
  fullAmountUpTo: number
  fullAmount: number
  partialUpTo: number
}

interface RegionalSurtaxConfig {
  region: string
  brackets: ProgressiveBracket[]
}

interface MunicipalSurtaxConfig {
  municipality: string
  rate: number
  /**
   * A hard exemption, not an allowance: one euro above the threshold the rate applies
   * to the whole taxable income, which produces a step in the net salary curve.
   */
  exemptionThreshold: number
}

export const TAX_YEAR_2026: TaxYearConfig = {
  year: 2026,

  /**
   * Source: INPS, Circolare n. 6 del 30/01/2026 (contribution values from 01/01/2026).
   * Sector rates: IVS 9.19% for both; industry adds the 0.30% CIGS contribution,
   * which small commercial businesses do not owe.
   */
  socialContributions: {
    employeeRateBySector: {
      commerce: 0.0919,
      industry: 0.0949,
    },
    additionalRateThreshold: 56_224,
    additionalRate: 0.01,
    annualCeiling: 122_295,
  },

  /**
   * Source: Legge di Bilancio 2026 (L. 199/2025, art. 1 co. 3), which cut the second
   * bracket from 35% to 33% with effect from 01/01/2026.
   */
  incomeTax: {
    brackets: [
      { upTo: 28_000, rate: 0.23 },
      { upTo: 50_000, rate: 0.33 },
      { upTo: null, rate: 0.43 },
    ],
  },

  /** Source: art. 13 TUIR, as amended for 2026. Relief is nil above 50.000 €. */
  employmentRelief: {
    flatAmountUpTo: 15_000,
    flatAmount: 1_955,
    firstTaperUpTo: 28_000,
    firstTaperBase: 1_910,
    firstTaperVariable: 1_190,
    secondTaperUpTo: 50_000,
    secondTaperBase: 1_910,
  },

  /**
   * Source: taglio del cuneo fiscale, confirmed for 2026.
   * Two distinct mechanisms sharing one policy: below 20.000 € an exempt sum paid in
   * the payslip, above it an ordinary tax relief tapering to zero at 40.000 €.
   */
  wedgeCut: {
    exemptBonusUpTo: 20_000,
    exemptBonusBrackets: [
      { upTo: 8_500, rate: 0.071 },
      { upTo: 15_000, rate: 0.053 },
      { upTo: 20_000, rate: 0.048 },
    ],
    reliefFlatUpTo: 32_000,
    reliefFlatAmount: 1_000,
    reliefTaperUpTo: 40_000,
  },

  /** Source: trattamento integrativo (ex "bonus Renzi"), art. 1 D.L. 3/2020. */
  supplementaryAllowance: {
    fullAmountUpTo: 15_000,
    fullAmount: 1_200,
    partialUpTo: 28_000,
  },

  /** Source: Regione Lombardia, addizionale regionale IRPEF (rates unchanged since 2022). */
  regionalSurtax: {
    region: 'Lombardia',
    brackets: [
      { upTo: 15_000, rate: 0.0123 },
      { upTo: 28_000, rate: 0.0158 },
      { upTo: 50_000, rate: 0.0172 },
      { upTo: null, rate: 0.0173 },
    ],
  },

  /**
   * Source: Comune di Milano. The 23.000 € threshold has been in force since 2020 and
   * still applies in 2026, no new delibera having been published.
   */
  municipalSurtax: {
    municipality: 'Milano',
    rate: 0.008,
    exemptionThreshold: 23_000,
  },
}
