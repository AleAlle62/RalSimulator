import type { MonthlyPaymentsCount, Payslip, PayslipKind, PayslipSchedule } from './types'

const ORDINARY_PAYSLIPS_PER_YEAR = 12

const EXTRA_PAYSLIP_KINDS: PayslipKind[] = ['thirteenth', 'fourteenth']

export interface PayslipScheduleParams {
  grossAnnualSalary: number
  monthlyPaymentsCount: MonthlyPaymentsCount
  netAnnualSalary: number
  socialContributionsTotal: number
  netTax: number
  localSurtaxesTotal: number
  /** Sums paid with the salary that are never taxed, spread over the ordinary payslips. */
  taxFreeAdditions: number
  marginalRate: number
}

/**
 * Splits the annual figures across actual payslips.
 *
 * Extra monthly payments are worth less in the hand than an ordinary month at identical
 * gross, because tax reliefs are consumed by the twelve ordinary payslips and local
 * surtaxes are withheld there too. Modelling that is the whole point of this module:
 * showing an averaged monthly figure would hide the difference employees actually see.
 *
 * The annual totals are authoritative — the parts are derived from them, so the payslips
 * always add back up to the annual net.
 */
export function calculatePayslipSchedule(params: PayslipScheduleParams): PayslipSchedule {
  const {
    grossAnnualSalary,
    monthlyPaymentsCount,
    netAnnualSalary,
    socialContributionsTotal,
    netTax,
    localSurtaxesTotal,
    taxFreeAdditions,
    marginalRate,
  } = params

  const grossPerPayslip = grossAnnualSalary / monthlyPaymentsCount
  const contributionRate = socialContributionsTotal / grossAnnualSalary
  const contributionsPerPayslip = grossPerPayslip * contributionRate
  const taxablePerPayslip = grossPerPayslip - contributionsPerPayslip

  const extraCount = monthlyPaymentsCount - ORDINARY_PAYSLIPS_PER_YEAR

  // Reliefs can wipe out the whole annual liability; withholding on the extras can never
  // exceed what is actually owed for the year.
  const uncappedExtraTax = taxablePerPayslip * marginalRate * extraCount
  const totalExtraTax = Math.min(uncappedExtraTax, netTax)
  const extraTaxPerPayslip = extraCount === 0 ? 0 : totalExtraTax / extraCount

  const extras: Payslip[] = EXTRA_PAYSLIP_KINDS.slice(0, extraCount).map((kind) => ({
    kind,
    gross: grossPerPayslip,
    socialContributions: contributionsPerPayslip,
    incomeTax: extraTaxPerPayslip,
    localSurtaxes: 0,
    net: grossPerPayslip - contributionsPerPayslip - extraTaxPerPayslip,
  }))

  const extrasNet = extras.reduce((sum, payslip) => sum + payslip.net, 0)

  const ordinary: Payslip = {
    kind: 'ordinary',
    gross: grossPerPayslip,
    socialContributions: contributionsPerPayslip,
    incomeTax: (netTax - totalExtraTax) / ORDINARY_PAYSLIPS_PER_YEAR,
    localSurtaxes: localSurtaxesTotal / ORDINARY_PAYSLIPS_PER_YEAR,
    net: (netAnnualSalary - extrasNet) / ORDINARY_PAYSLIPS_PER_YEAR,
  }

  return { ordinary, extras }
}

export { ORDINARY_PAYSLIPS_PER_YEAR }
