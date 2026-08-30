/**
 * Money and percentages, formatted the Italian way, in one place.
 *
 * Every figure this product shows is a euro amount somebody may check against a payslip, so the
 * formatting is fixed rather than per-call: two decimals always, thousands separated, and the
 * sign carried explicitly where a line is a deduction or a credit. The README requires
 * that sign for a reason beyond style — colour alone must never say whether money left or
 * arrived, since red/green is the most common form of colour blindness.
 */

const euro = new Intl.NumberFormat('it-IT', {
  style: 'currency',
  currency: 'EUR',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
  // Italian defaults to CLDR's "min2" grouping, which leaves four-digit amounts unseparated:
  // 1910,42 next to 25.967,22 in the same column. Forced on, because these figures are meant
  // to be compared down a column and against a payslip.
  useGrouping: true,
});

const percent = new Intl.NumberFormat('it-IT', {
  style: 'percent',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
});

export type LineSign = 'minus' | 'plus' | 'equals' | 'none';

export function useCurrency() {
  const formatEuro = (value: number) => euro.format(value);

  const formatPercent = (rate: number) => percent.format(rate);

  /** The operator that opens a breakdown line, read aloud by screen readers as a word. */
  const signLabel = (sign: LineSign) => {
    switch (sign) {
      case 'minus':
        return 'meno';
      case 'plus':
        return 'più';
      case 'equals':
        return 'uguale';
      default:
        return '';
    }
  };

  const signSymbol = (sign: LineSign) => {
    switch (sign) {
      case 'minus':
        return '−';
      case 'plus':
        return '+';
      case 'equals':
        return '=';
      default:
        return '';
    }
  };

  return { formatEuro, formatPercent, signLabel, signSymbol };
}
