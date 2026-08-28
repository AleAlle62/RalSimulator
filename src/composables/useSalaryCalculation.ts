import { reactive, ref } from 'vue'

import { calculateSalary, type SalaryBreakdown, type SalaryInput } from '@/domain'

const MINIMUM_GROSS_SALARY = 1
const MAXIMUM_GROSS_SALARY = 1_000_000

/**
 * Holds the form state and turns it into a breakdown on demand.
 *
 * The result is deliberately not a computed value: the brief asks for an explicit
 * "Calcola" action, and recalculating on every keystroke would make the animated figures
 * unreadable while typing.
 */
export function useSalaryCalculation() {
  const form = reactive<SalaryInput>({
    grossAnnualSalary: 35_000,
    sector: 'commerce',
    monthlyPaymentsCount: 14,
  })

  const result = ref<SalaryBreakdown | null>(null)
  const validationError = ref<string | null>(null)

  /** The one place user input is trusted or rejected; the domain assumes valid figures. */
  function findValidationError(): string | null {
    const { grossAnnualSalary } = form

    if (!Number.isFinite(grossAnnualSalary) || grossAnnualSalary < MINIMUM_GROSS_SALARY) {
      return 'Inserisci una RAL maggiore di zero.'
    }

    if (grossAnnualSalary > MAXIMUM_GROSS_SALARY) {
      return 'Il simulatore si ferma a 1.000.000 €.'
    }

    return null
  }

  /** Bumped on every successful run so the animated figures restart from zero. */
  const revision = ref(0)

  function calculate() {
    validationError.value = findValidationError()

    if (validationError.value !== null) {
      result.value = null
      return
    }

    result.value = calculateSalary({ ...form })
    revision.value += 1
  }

  return { form, result, validationError, revision, calculate }
}
