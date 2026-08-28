import { findBracketRate, taperedAmount } from './brackets'
import type { TaxReliefs } from './types'
import type { TaxYearConfig } from './taxYear2026'

/**
 * Employment income relief (art. 13 TUIR): a flat amount on low incomes, then two linear
 * tapers that bring it to zero at 50.000 €.
 */
function calculateEmploymentRelief(taxableIncome: number, config: TaxYearConfig): number {
  const {
    flatAmountUpTo,
    flatAmount,
    firstTaperUpTo,
    firstTaperBase,
    firstTaperVariable,
    secondTaperUpTo,
    secondTaperBase,
  } = config.employmentRelief

  if (taxableIncome <= flatAmountUpTo) {
    return flatAmount
  }

  if (taxableIncome <= firstTaperUpTo) {
    const taperedShare =
      (firstTaperVariable * (firstTaperUpTo - taxableIncome)) / (firstTaperUpTo - flatAmountUpTo)
    return firstTaperBase + taperedShare
  }

  if (taxableIncome <= secondTaperUpTo) {
    return taperedAmount(secondTaperBase, taxableIncome, firstTaperUpTo, secondTaperUpTo)
  }

  return 0
}

/**
 * The "taglio del cuneo fiscale" is two different instruments under one name. Below the
 * threshold it is an exempt sum paid in the payslip, which never touches income tax;
 * above it, an ordinary relief. They are mutually exclusive by design.
 */
function calculateWedgeCut(
  taxableIncome: number,
  config: TaxYearConfig,
): { exemptBonus: number; relief: number } {
  const { exemptBonusUpTo, exemptBonusBrackets, reliefFlatUpTo, reliefFlatAmount, reliefTaperUpTo } =
    config.wedgeCut

  if (taxableIncome <= exemptBonusUpTo) {
    const rate = findBracketRate(taxableIncome, exemptBonusBrackets)
    return { exemptBonus: taxableIncome * rate, relief: 0 }
  }

  return {
    exemptBonus: 0,
    relief: taperedAmount(reliefFlatAmount, taxableIncome, reliefFlatUpTo, reliefTaperUpTo),
  }
}

/**
 * Trattamento integrativo. On the lowest incomes it is granted in full provided gross tax
 * exceeds the employment relief; on middle incomes only to the extent reliefs outrun the
 * gross tax, which in this simplified model (no dependants, no mortgage interest) is
 * usually nil.
 */
function calculateSupplementaryAllowance(
  taxableIncome: number,
  grossTax: number,
  employmentRelief: number,
  wedgeCutRelief: number,
  config: TaxYearConfig,
): number {
  const { fullAmountUpTo, fullAmount, partialUpTo } = config.supplementaryAllowance

  if (taxableIncome <= fullAmountUpTo) {
    if (grossTax <= employmentRelief) {
      return 0
    }
    return fullAmount
  }

  if (taxableIncome <= partialUpTo) {
    const reliefsExceedingTax = employmentRelief + wedgeCutRelief - grossTax
    return Math.min(fullAmount, Math.max(0, reliefsExceedingTax))
  }

  return 0
}

export function calculateTaxReliefs(
  taxableIncome: number,
  grossTax: number,
  config: TaxYearConfig,
): TaxReliefs {
  const employmentRelief = calculateEmploymentRelief(taxableIncome, config)
  const wedgeCut = calculateWedgeCut(taxableIncome, config)
  const supplementaryAllowance = calculateSupplementaryAllowance(
    taxableIncome,
    grossTax,
    employmentRelief,
    wedgeCut.relief,
    config,
  )

  return {
    employmentRelief,
    wedgeCutRelief: wedgeCut.relief,
    exemptWedgeCutBonus: wedgeCut.exemptBonus,
    supplementaryAllowance,
    totalDeductedFromTax: employmentRelief + wedgeCut.relief,
  }
}
