import { describe, it, expect } from 'vitest'

import { calculateSalary } from '../salaryCalculator'
import type { SalaryInput } from '../types'

function inputFor(grossAnnualSalary: number, overrides: Partial<SalaryInput> = {}): SalaryInput {
  return {
    grossAnnualSalary,
    sector: 'commerce',
    monthlyPaymentsCount: 14,
    ...overrides,
  }
}

/**
 * Reference case computed by hand from the 2026 parameters, step by step:
 *
 *   contributions   35.000 x 9,19%                              =  3.216,50
 *   taxable income  35.000 - 3.216,50                           = 31.783,50
 *   gross tax       28.000 x 23% + 3.783,50 x 33%               =  7.688,56
 *   employment      1.910 x (50.000 - 31.783,50) / 22.000       =  1.581,52
 *   wedge cut       flat, income sits below the 32.000 taper    =  1.000,00
 *   net tax         7.688,56 - 1.581,52 - 1.000                 =  5.107,03
 *   regional        15.000 x 1,23% + 13.000 x 1,58%
 *                     + 3.783,50 x 1,72%                        =    454,98
 *   municipal       31.783,50 x 0,8%, threshold passed          =    254,27
 *   net salary      35.000 - 3.216,50 - 5.107,03 - 454,98 - 254,27 = 25.967,22
 */
describe('reference case: 35.000 gross, commerce, 14 payments', () => {
  const result = calculateSalary(inputFor(35_000))

  it('should withhold contributions before computing the taxable income', () => {
    expect(result.socialContributions.total).toBeCloseTo(3_216.5, 2)
    expect(result.taxableIncome).toBeCloseTo(31_783.5, 2)
  })

  it('should compute gross tax, reliefs and net tax', () => {
    expect(result.incomeTax.grossTax).toBeCloseTo(7_688.555, 2)
    expect(result.incomeTax.reliefs.employmentRelief).toBeCloseTo(1_581.52, 2)
    expect(result.incomeTax.reliefs.wedgeCutRelief).toBeCloseTo(1_000, 2)
    expect(result.incomeTax.netTax).toBeCloseTo(5_107.03, 2)
  })

  it('should compute both local surtaxes', () => {
    expect(result.localSurtaxes.regional).toBeCloseTo(454.98, 2)
    expect(result.localSurtaxes.municipal).toBeCloseTo(254.27, 2)
  })

  it('should reach the expected annual net salary', () => {
    expect(result.netAnnualSalary).toBeCloseTo(25_967.22, 2)
  })
})

describe('payslip schedule', () => {
  it('should pay extra months less than an ordinary month at the same gross', () => {
    const { payslips } = calculateSalary(inputFor(35_000))

    expect(payslips.extras).toHaveLength(2)
    for (const extra of payslips.extras) {
      expect(extra.gross).toBeCloseTo(payslips.ordinary.gross, 2)
      expect(extra.net).toBeLessThan(payslips.ordinary.net)
    }
  })

  it('should split the annual net across every payslip without losing a cent', () => {
    for (const count of [12, 13, 14] as const) {
      const result = calculateSalary(inputFor(35_000, { monthlyPaymentsCount: count }))
      const extrasNet = result.payslips.extras.reduce((sum, payslip) => sum + payslip.net, 0)
      const total = result.payslips.ordinary.net * 12 + extrasNet

      expect(total).toBeCloseTo(result.netAnnualSalary, 2)
    }
  })

  it('should produce no extra payslips on a twelve month schedule', () => {
    const { payslips } = calculateSalary(inputFor(35_000, { monthlyPaymentsCount: 12 }))

    expect(payslips.extras).toHaveLength(0)
  })

  it('should never withhold more tax on the extras than is owed for the year', () => {
    const result = calculateSalary(inputFor(16_000))
    const extrasTax = result.payslips.extras.reduce((sum, payslip) => sum + payslip.incomeTax, 0)

    expect(extrasTax).toBeLessThanOrEqual(result.incomeTax.netTax + 0.01)
    expect(result.payslips.ordinary.incomeTax).toBeGreaterThanOrEqual(0)
  })
})

describe('consistency across the income range', () => {
  const salaries = [12_000, 18_000, 25_000, 35_000, 55_000, 80_000, 130_000]

  it('should always keep the net below the gross', () => {
    for (const salary of salaries) {
      const result = calculateSalary(inputFor(salary))

      expect(result.netAnnualSalary).toBeLessThan(salary)
      expect(result.netAnnualSalary).toBeGreaterThan(0)
    }
  })

  it('should balance gross against withholdings, exempt sums and net', () => {
    for (const salary of salaries) {
      const result = calculateSalary(inputFor(salary))
      const exemptSums =
        result.incomeTax.reliefs.exemptWedgeCutBonus +
        result.incomeTax.reliefs.supplementaryAllowance

      expect(result.netAnnualSalary + result.totalWithholdings - exemptSums).toBeCloseTo(salary, 2)
    }
  })

  it('should raise the net salary whenever the gross salary rises', () => {
    // The Milano exemption is a deliberate exception, tested separately.
    const nets = salaries.map((salary) => calculateSalary(inputFor(salary)).netAnnualSalary)

    nets.reduce((previousNet, currentNet) => {
      expect(currentNet).toBeGreaterThan(previousNet)
      return currentNet
    })
  })

  it('should leave an industry employee with less net than a commerce one', () => {
    const commerce = calculateSalary(inputFor(35_000, { sector: 'commerce' }))
    const industry = calculateSalary(inputFor(35_000, { sector: 'industry' }))

    expect(industry.netAnnualSalary).toBeLessThan(commerce.netAnnualSalary)
  })
})

describe('known discontinuity at the Milano exemption threshold', () => {
  it('should drop the net salary just past the threshold, as the rules require', () => {
    // The exemption is not an allowance, so crossing 23.000 of taxable income charges
    // the 0,8% on the whole amount at once. Documented in the README as a known effect.
    const below = calculateSalary(inputFor(25_320))
    const above = calculateSalary(inputFor(25_330))

    expect(below.taxableIncome).toBeLessThan(23_000)
    expect(above.taxableIncome).toBeGreaterThan(23_000)
    expect(above.netAnnualSalary).toBeLessThan(below.netAnnualSalary)
  })
})
