import { calculateSocialContributions } from './socialContributions'
import { calculateIncomeTax, findMarginalRate } from './incomeTax'
import { calculateLocalSurtaxes } from './localSurtaxes'
import { calculatePayslipSchedule } from './payslipSchedule'
import { TAX_YEAR_2026, type TaxYearConfig } from './taxYear2026'
import type { SalaryBreakdown, SalaryInput } from './types'

/**
 * Walks a gross annual salary down to the net, in the order payroll applies the rules:
 * contributions first (they shrink the taxable base), then income tax net of reliefs,
 * then local surtaxes.
 *
 * The tax year is injected rather than imported at the point of use, so the same engine
 * can price a different year by passing a different configuration.
 */
export function calculateSalary(
  input: SalaryInput,
  config: TaxYearConfig = TAX_YEAR_2026,
): SalaryBreakdown {
  const { grossAnnualSalary, sector, monthlyPaymentsCount } = input

  const socialContributions = calculateSocialContributions(grossAnnualSalary, sector, config)
  const taxableIncome = grossAnnualSalary - socialContributions.total

  const incomeTax = calculateIncomeTax(taxableIncome, config)
  const localSurtaxes = calculateLocalSurtaxes(taxableIncome, config)

  const totalWithholdings = socialContributions.total + incomeTax.netTax + localSurtaxes.total
  const taxFreeAdditions =
    incomeTax.reliefs.exemptWedgeCutBonus + incomeTax.reliefs.supplementaryAllowance
  const netAnnualSalary = grossAnnualSalary - totalWithholdings + taxFreeAdditions

  const payslips = calculatePayslipSchedule({
    grossAnnualSalary,
    monthlyPaymentsCount,
    netAnnualSalary,
    socialContributionsTotal: socialContributions.total,
    netTax: incomeTax.netTax,
    localSurtaxesTotal: localSurtaxes.total,
    taxFreeAdditions,
    marginalRate: findMarginalRate(taxableIncome, config),
  })

  return {
    input,
    grossAnnualSalary,
    socialContributions,
    taxableIncome,
    incomeTax,
    localSurtaxes,
    totalWithholdings,
    netAnnualSalary,
    effectiveWithholdingRate: totalWithholdings / grossAnnualSalary,
    payslips,
  }
}
