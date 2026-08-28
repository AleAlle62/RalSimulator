/*
 * Italian CLDR omits the thousands separator on four-digit numbers, so 3217 would print next to
 * 31.784 in the same column. A ledger reads better with grouping always on.
 *
 * `useGrouping: 'always'` is ES2023 and supported by every current browser, but TypeScript's
 * NumberFormatOptions still declares the field as a boolean, hence the assertion.
 */
const ALWAYS_GROUP = { useGrouping: 'always' } as unknown as Intl.NumberFormatOptions

const euroFormatter = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
  ...ALWAYS_GROUP,
})

const euroWithCentsFormatter = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
  ...ALWAYS_GROUP,
})

const percentFormatter = new Intl.NumberFormat('it-IT', {
  style: 'percent',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})

export function formatEuro(value: number): string {
  return euroFormatter.format(value)
}

export function formatEuroWithCents(value: number): string {
  return euroWithCentsFormatter.format(value)
}

export function formatPercent(value: number): string {
  return percentFormatter.format(value)
}
