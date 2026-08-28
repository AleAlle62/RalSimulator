import { describe, it, expect } from 'vitest'

import { calculateSocialContributions } from '../socialContributions'
import { calculateIncomeTax, findMarginalRate } from '../incomeTax'
import { calculateLocalSurtaxes } from '../localSurtaxes'
import { TAX_YEAR_2026 as config } from '../taxYear2026'

describe('social contributions', () => {
  it('should apply the commerce rate of 9.19% below every threshold', () => {
    const result = calculateSocialContributions(35_000, 'commerce', config)

    expect(result.total).toBeCloseTo(3_216.5, 2)
    expect(result.additionalRateAmount).toBe(0)
  })

  it('should charge industry 0.30% more than commerce for the CIGS contribution', () => {
    const commerce = calculateSocialContributions(35_000, 'commerce', config)
    const industry = calculateSocialContributions(35_000, 'industry', config)

    expect(industry.total - commerce.total).toBeCloseTo(35_000 * 0.003, 2)
  })

  it('should add one point only on the income above the first pension band', () => {
    const result = calculateSocialContributions(66_224, 'commerce', config)

    expect(result.additionalRateAmount).toBeCloseTo(100, 2)
    expect(result.total).toBeCloseTo(66_224 * 0.0919 + 100, 2)
  })

  it('should stop charging contributions above the annual ceiling', () => {
    const atCeiling = calculateSocialContributions(122_295, 'commerce', config)
    const aboveCeiling = calculateSocialContributions(200_000, 'commerce', config)

    expect(aboveCeiling.total).toBeCloseTo(atCeiling.total, 2)
    expect(aboveCeiling.contributoryBase).toBe(122_295)
  })
})

describe('income tax', () => {
  it('should slice gross tax across the 23/33/43 brackets', () => {
    const expected = 28_000 * 0.23 + 22_000 * 0.33 + 10_000 * 0.43

    expect(calculateIncomeTax(60_000, config).grossTax).toBeCloseTo(expected, 2)
  })

  it('should taper the employment relief to zero above 50.000', () => {
    expect(calculateIncomeTax(51_000, config).reliefs.employmentRelief).toBe(0)
  })

  it('should grant the full wedge cut relief between 20.000 and 32.000', () => {
    expect(calculateIncomeTax(31_783.5, config).reliefs.wedgeCutRelief).toBeCloseTo(1_000, 2)
  })

  it('should taper the wedge cut relief to zero at 40.000', () => {
    expect(calculateIncomeTax(36_000, config).reliefs.wedgeCutRelief).toBeCloseTo(500, 2)
    expect(calculateIncomeTax(40_000, config).reliefs.wedgeCutRelief).toBe(0)
  })

  it('should pay the wedge cut as an exempt sum, not a relief, below 20.000', () => {
    const reliefs = calculateIncomeTax(18_000, config).reliefs

    expect(reliefs.exemptWedgeCutBonus).toBeCloseTo(18_000 * 0.048, 2)
    expect(reliefs.wedgeCutRelief).toBe(0)
  })

  it('should grant the supplementary allowance below 15.000 when gross tax exceeds the relief', () => {
    // 14.000 x 23% = 3.220 of gross tax against a 1.955 relief, so the allowance is due.
    expect(calculateIncomeTax(14_000, config).reliefs.supplementaryAllowance).toBe(1_200)
  })

  it('should deny the supplementary allowance when the relief already absorbs the gross tax', () => {
    // 8.000 x 23% = 1.840 of gross tax against the same 1.955 relief: nothing left to top up.
    expect(calculateIncomeTax(8_000, config).reliefs.supplementaryAllowance).toBe(0)
  })

  it('should never return a negative net tax when reliefs exceed gross tax', () => {
    expect(calculateIncomeTax(8_000, config).netTax).toBe(0)
  })

  it('should report the rate of the highest bracket reached', () => {
    expect(findMarginalRate(20_000, config)).toBe(0.23)
    expect(findMarginalRate(31_783.5, config)).toBe(0.33)
    expect(findMarginalRate(80_000, config)).toBe(0.43)
  })
})

describe('local surtaxes', () => {
  it('should slice the Lombardia surtax across its own brackets', () => {
    const expected = 15_000 * 0.0123 + 13_000 * 0.0158 + 3_783.5 * 0.0172

    expect(calculateLocalSurtaxes(31_783.5, config).regional).toBeCloseTo(expected, 2)
  })

  it('should exempt the Milano surtax up to 23.000', () => {
    expect(calculateLocalSurtaxes(23_000, config).municipal).toBe(0)
  })

  it('should charge the Milano surtax on the whole income once the threshold is passed', () => {
    // The threshold is an exemption, not an allowance: this step is in the rules.
    expect(calculateLocalSurtaxes(23_001, config).municipal).toBeCloseTo(23_001 * 0.008, 2)
  })
})
