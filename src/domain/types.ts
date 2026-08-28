export type Sector = 'commerce' | 'industry'

export type MonthlyPaymentsCount = 12 | 13 | 14

export interface SalaryInput {
  grossAnnualSalary: number
  sector: Sector
  monthlyPaymentsCount: MonthlyPaymentsCount
}

export interface ProgressiveBracket {
  /** Upper bound of the bracket. `null` means "no upper bound". */
  upTo: number | null
  rate: number
}

export interface SocialContributions {
  /** Portion of gross salary actually subject to contributions, after the annual ceiling. */
  contributoryBase: number
  baseRate: number
  baseAmount: number
  additionalRateAmount: number
  total: number
}

export interface TaxReliefs {
  employmentRelief: number
  wedgeCutRelief: number
  /** Paid out with the salary and not part of taxable income, so it is added to the net. */
  exemptWedgeCutBonus: number
  supplementaryAllowance: number
  /** Reliefs that reduce gross income tax (excludes the exempt bonus and the allowance). */
  totalDeductedFromTax: number
}

export interface IncomeTax {
  grossTax: number
  reliefs: TaxReliefs
  netTax: number
}

export interface LocalSurtaxes {
  regional: number
  municipal: number
  total: number
}

export type PayslipKind = 'ordinary' | 'thirteenth' | 'fourteenth'

export interface Payslip {
  kind: PayslipKind
  gross: number
  socialContributions: number
  incomeTax: number
  localSurtaxes: number
  net: number
}

export interface PayslipSchedule {
  ordinary: Payslip
  extras: Payslip[]
}

export interface SalaryBreakdown {
  input: SalaryInput
  grossAnnualSalary: number
  socialContributions: SocialContributions
  taxableIncome: number
  incomeTax: IncomeTax
  localSurtaxes: LocalSurtaxes
  totalWithholdings: number
  netAnnualSalary: number
  /** Share of gross salary withheld as contributions and taxes. */
  effectiveWithholdingRate: number
  payslips: PayslipSchedule
}
