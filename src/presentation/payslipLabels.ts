import type { PayslipKind, Sector } from '@/domain'

/**
 * The domain speaks in kinds, not in words. Naming lives here so the calculation engine
 * stays free of anything language- or interface-specific.
 */
export const PAYSLIP_LABELS: Record<PayslipKind, string> = {
  ordinary: 'Mensilità ordinaria',
  thirteenth: 'Tredicesima',
  fourteenth: 'Quattordicesima',
}

export const PAYSLIP_OCCURRENCES: Record<PayslipKind, string> = {
  ordinary: '(×12)',
  thirteenth: '(×1)',
  fourteenth: '(×1)',
}

export const SECTOR_LABELS: Record<Sector, string> = {
  commerce: 'Commercio e terziario',
  industry: 'Industria',
}

export const SECTOR_DESCRIPTIONS: Record<Sector, string> = {
  commerce: 'Aziende fino a 50 dipendenti',
  industry: 'Oltre 50 dipendenti, con contributo CIGS',
}
