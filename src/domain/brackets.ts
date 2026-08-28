import type { ProgressiveBracket } from './types'

/**
 * Applies each rate to its own slice of income, the way IRPEF and the regional surtax work:
 * earning one euro more never lowers the net salary.
 */
export function applyProgressiveBrackets(amount: number, brackets: ProgressiveBracket[]): number {
  let tax = 0
  let lowerBound = 0

  for (const bracket of brackets) {
    if (amount <= lowerBound) {
      break
    }

    const upperBound = bracket.upTo ?? amount
    const taxableInBracket = Math.min(amount, upperBound) - lowerBound
    tax += taxableInBracket * bracket.rate
    lowerBound = upperBound
  }

  return tax
}

/**
 * Returns the rate of the bracket the amount falls into, for benefits whose percentage is
 * selected by income band and then applied to the whole amount rather than sliced.
 */
export function findBracketRate(amount: number, brackets: ProgressiveBracket[]): number {
  const bracket = brackets.find((candidate) => candidate.upTo === null || amount <= candidate.upTo)
  return bracket?.rate ?? 0
}

/** Linear taper from a full amount down to zero across a range, as used by several reliefs. */
export function taperedAmount(
  fullAmount: number,
  income: number,
  taperStart: number,
  taperEnd: number,
): number {
  if (income <= taperStart) {
    return fullAmount
  }
  if (income >= taperEnd) {
    return 0
  }

  return (fullAmount * (taperEnd - income)) / (taperEnd - taperStart)
}
