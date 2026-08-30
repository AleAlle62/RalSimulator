/**
 * The shapes the API actually returns. Mirrors app/Http/Resources on the Laravel side and the
 * domain objects underneath it; `result` is the stored snapshot, so it is read as it was
 * written and never recomputed here.
 */

export type Sector = 'commerce' | 'industry';

export type MonthlyPaymentsCount = 12 | 13 | 14;

export interface SimulationInput {
  gross_annual_salary: number;
  monthly_payments_count: MonthlyPaymentsCount;
  sector: Sector;
  municipality: string;
}

export interface Contributions {
  contributoryBase: number;
  baseRate: number;
  baseAmount: number;
  additionalRateAmount: number;
  total: number;
}

export interface Reliefs {
  employmentRelief: number;
  wedgeCutRelief: number;
  exemptWedgeCutBonus: number;
  supplementaryAllowance: number;
}

export interface Surtaxes {
  regional: number;
  municipal: number;
  total: number;
}

export type PayslipKind = 'ordinary' | 'thirteenth' | 'fourteenth';

export interface Payslip {
  kind: PayslipKind;
  gross: number;
  contributions: number;
  irpef: number;
  surtaxes: number;
  taxFreeAdditions: number;
  net: number;
}

export interface PayslipSchedule {
  ordinary: Payslip;
  extras: Payslip[];
}

export interface SalaryBreakdown {
  input: { grossAnnualSalary: number; monthlyPaymentsCount: number; sector: Sector };
  year: number;
  grossAnnualSalary: number;
  contributions: Contributions;
  taxableIncome: number;
  grossIrpef: number;
  reliefs: Reliefs;
  netIrpef: number;
  surtaxes: Surtaxes;
  totalWithholdings: number;
  /**
   * Exempt sums are added to the net, they are not a slice of the gross. Any chart that treats
   * them as a share of the RAL will show proportions adding up to more than the whole.
   */
  taxFreeAdditions: number;
  netAnnualSalary: number;
  payslips: PayslipSchedule;
}

export interface Simulation {
  id: number;
  token: string;
  createdAt: string;
  grossAnnualSalary: number;
  monthlyPaymentsCount: number;
  sector: Sector;
  municipality: string | null;
  region: string | null;
  result: SalaryBreakdown;
  /** Belongs to whoever is asking. Always false for a guest. */
  mine: boolean;
  /** Has no owner yet, so signing in is enough to make it yours. */
  claimable: boolean;
}

export interface User {
  id: number;
  name: string;
  email: string;
}
