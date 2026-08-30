import { defineStore } from 'pinia';
import { ref } from 'vue';
import { api } from '@/services/api';
import type { MonthlyPaymentsCount, Sector, Simulation } from '@/types/simulation';

export interface Municipality {
  name: string;
  province: string;
  region: string;
  rate: number;
  exemptionThreshold: number;
}

/**
 * The wizard's answers and the result they produce.
 *
 * No arithmetic happens here. The engine lives in PHP and is the single source of truth for
 * every figure (CLAUDE.md, decision 1); this store collects four answers, posts them, and holds
 * the snapshot that comes back. If a number ever needs to appear on screen that the API did not
 * return, the fix is an API change, not a calculation in the client.
 */
export const useSimulationStore = defineStore('simulation', () => {
  const grossAnnualSalary = ref<number | null>(null);
  const monthlyPaymentsCount = ref<MonthlyPaymentsCount>(14);
  const sector = ref<Sector>('commerce');
  const municipality = ref<string | null>(null);

  const municipalities = ref<Municipality[]>([]);
  const result = ref<Simulation | null>(null);
  const running = ref(false);

  async function loadMunicipalities() {
    if (municipalities.value.length > 0) return;

    const response = await api.get<{ data: Municipality[] }>('/api/tax-years/2026/municipalities');
    municipalities.value = response.data;
  }

  async function run(): Promise<Simulation> {
    running.value = true;
    try {
      // The year is deliberately absent: the server picks the published one, so a client
      // cannot ask to be taxed under an older, kinder set of rates.
      const response = await api.post<{ data: Simulation }>('/api/simulations', {
        gross_annual_salary: grossAnnualSalary.value,
        monthly_payments_count: monthlyPaymentsCount.value,
        sector: sector.value,
        municipality: municipality.value,
      });

      result.value = response.data;
      return response.data;
    } finally {
      running.value = false;
    }
  }

  async function loadByToken(token: string) {
    const response = await api.get<{ data: Simulation }>(`/api/simulations/${token}`);
    result.value = response.data;

    // Keep the answers in step with the snapshot, so reopening a shared link and then editing
    // one field re-runs the same simulation rather than a half-empty one.
    grossAnnualSalary.value = response.data.grossAnnualSalary;
    monthlyPaymentsCount.value = response.data.monthlyPaymentsCount as MonthlyPaymentsCount;
    sector.value = response.data.sector;
    municipality.value = response.data.municipality;
  }

  /** The signed-in user's own saved simulations. Requires a session; 401 is left to the caller. */
  async function listMine(): Promise<Simulation[]> {
    const response = await api.get<{ data: Simulation[] }>('/api/me/simulations');
    return response.data;
  }

  /**
   * Make the open simulation belong to the signed-in user.
   *
   * Every simulation is already stored the moment it is calculated — this only puts an owner on
   * one that had none, so it turns up in "Le mie simulazioni" instead of living solely in
   * whoever's address bar.
   */
  async function claim(token: string) {
    const response = await api.post<{ data: Simulation }>(`/api/me/simulations/${token}/claim`);
    result.value = response.data;
  }

  async function remove(id: number) {
    await api.delete(`/api/me/simulations/${id}`);
  }

  function reset() {
    grossAnnualSalary.value = null;
    monthlyPaymentsCount.value = 14;
    sector.value = 'commerce';
    municipality.value = null;
    result.value = null;
  }

  return {
    grossAnnualSalary,
    monthlyPaymentsCount,
    sector,
    municipality,
    municipalities,
    result,
    running,
    loadMunicipalities,
    run,
    loadByToken,
    listMine,
    claim,
    remove,
    reset,
  };
});
