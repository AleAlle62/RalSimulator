<template>
  <div class="result">
    <div class="result__shell">
      <AppHeader />

      <template v-if="breakdown">
        <section class="result__headline panel-solid" aria-labelledby="net-heading">
          <h1 id="net-heading" class="result__heading">
            Su {{ formatEuro(breakdown.grossAnnualSalary) }} lordi, ti restano
          </h1>
          <p class="result__net tabular">{{ formatEuro(breakdown.netAnnualSalary) }}</p>
          <p class="result__per-month">
            all'anno, cioè
            <strong class="tabular">{{ formatEuro(breakdown.payslips.ordinary.net) }}</strong>
            in una busta ordinaria
            <span class="result__muted">
              ({{ simulation?.municipality }}, {{ sectorLabel }},
              {{ breakdown.input.monthlyPaymentsCount }} mensilità)
            </span>
          </p>
        </section>

        <section class="result__split panel-solid" aria-labelledby="split-heading">
          <h2 id="split-heading" class="result__h2">Dove finisce il lordo</h2>

          <div class="split" role="img" :aria-label="splitDescription">
            <span
              v-for="slice in slices"
              :key="slice.label"
              class="split__slice"
              :style="{ width: `${slice.share * 100}%`, background: slice.color }"
            />
          </div>

          <ul class="legend">
            <li v-for="slice in slices" :key="slice.label" class="legend__item">
              <span
                class="legend__swatch"
                :style="{ background: slice.color }"
                aria-hidden="true"
              />
              <span class="legend__label">{{ slice.label }}</span>
              <span class="legend__amount tabular">{{ formatEuro(slice.amount) }}</span>
            </li>
          </ul>

          <p v-if="breakdown.taxFreeAdditions > 0" class="result__exempt">
            A questo si <strong>aggiungono</strong>
            <strong class="tabular">{{ formatEuro(breakdown.taxFreeAdditions) }}</strong>
            di somme esenti, che non escono dal lordo: per questo il netto non è semplicemente una
            fetta della barra qui sopra.
          </p>
        </section>

        <section class="result__detail panel-solid" aria-labelledby="detail-heading">
          <h2 id="detail-heading" class="result__h2">Riga per riga</h2>

          <table class="detail">
            <caption class="result__sr">
              Dal lordo annuo al netto annuo, una trattenuta per riga
            </caption>
            <tbody>
              <BreakdownRow label="RAL lorda" :amount="breakdown.grossAnnualSalary" sign="none" />
              <BreakdownRow
                label="Contributi INPS"
                :amount="breakdown.contributions.total"
                sign="minus"
                :note="`${formatPercent(breakdown.contributions.baseRate)} sulla base contributiva`"
              />
              <BreakdownRow
                label="IRPEF netta"
                :amount="breakdown.netIrpef"
                sign="minus"
                :note="`Lorda ${formatEuro(breakdown.grossIrpef)}, meno ${formatEuro(reliefsFromTax)} di detrazioni`"
              />
              <BreakdownRow
                label="Addizionale regionale"
                :amount="breakdown.surtaxes.regional"
                sign="minus"
                :note="simulation?.region ?? undefined"
              />
              <BreakdownRow
                label="Addizionale comunale"
                :amount="breakdown.surtaxes.municipal"
                sign="minus"
                :note="simulation?.municipality ?? undefined"
              />
              <BreakdownRow
                v-if="breakdown.reliefs.exemptWedgeCutBonus > 0"
                label="Somma esente, taglio del cuneo"
                :amount="breakdown.reliefs.exemptWedgeCutBonus"
                sign="plus"
                note="Non è una detrazione: arriva in busta e non viene tassata"
              />
              <BreakdownRow
                v-if="breakdown.reliefs.supplementaryAllowance > 0"
                label="Trattamento integrativo"
                :amount="breakdown.reliefs.supplementaryAllowance"
                sign="plus"
                note="Anche questo si aggiunge al netto, non abbatte l'imposta"
              />
              <BreakdownRow label="Netto annuo" :amount="breakdown.netAnnualSalary" sign="equals" />
            </tbody>
          </table>
        </section>

        <section class="result__payslips panel-solid" aria-labelledby="payslips-heading">
          <h2 id="payslips-heading" class="result__h2">Le buste</h2>
          <p class="result__note">
            Le mensilità aggiuntive rendono meno di una ordinaria a parità di lordo: non portano
            detrazioni né addizionali, che sono già state applicate sulle buste ordinarie.
          </p>

          <div class="payslips">
            <article
              v-for="slip in allPayslips"
              :key="slip.kind"
              class="payslip"
              :class="{ 'payslip--extra': slip.kind !== 'ordinary' }"
            >
              <h3 class="payslip__kind">{{ payslipLabel(slip.kind) }}</h3>
              <p class="payslip__net tabular">{{ formatEuro(slip.net) }}</p>
              <p class="payslip__gross tabular">su {{ formatEuro(slip.gross) }} lordi</p>
            </article>
          </div>
        </section>

        <section class="result__assumptions">
          <h2 class="result__h2">Cosa non è compreso</h2>
          <p class="result__note">
            Lavoro dipendente, nessun familiare a carico, nessun onere detraibile, nessun TFR. Il
            costo per l'azienda non è calcolato: qui c'è solo la quota a tuo carico. Non sostituisce
            il conteggio di un consulente del lavoro.
          </p>

          <div class="result__actions">
            <q-btn flat no-caps dark color="primary" label="Rifai il calcolo" to="/simulazione" />
            <q-btn
              flat
              no-caps
              dark
              color="primary"
              :label="copied ? 'Link copiato' : 'Copia il link'"
              @click="copyLink"
            />
          </div>
        </section>
      </template>

      <p v-else-if="failure" class="result__failure panel-solid" role="alert">{{ failure }}</p>
      <p v-else class="result__loading">Carico la simulazione…</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import AppHeader from '@/components/AppHeader.vue';
import BreakdownRow from '@/components/BreakdownRow.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useSimulationStore } from '@/stores/simulation';
import type { PayslipKind } from '@/types/simulation';

/**
 * The screen the reviewer checks and the screen the employee learns from, which is why every
 * total here is followed by the lines that produce it (docs/PRODOTTO.md, principle 1).
 *
 * Solid panels throughout, no glass: figures are read here.
 *
 * The one thing this page must not get wrong is the exempt sums. They are not a slice of the
 * gross, they are added to the net, so the bar divides the RAL only and they are stated
 * separately underneath. Folding them into "resta a te" would make the bar promise more than
 * the whole.
 */

const route = useRoute();
const store = useSimulationStore();
const { formatEuro, formatPercent } = useCurrency();

const failure = ref<string | null>(null);
const copied = ref(false);

const simulation = computed(() => store.result);
const breakdown = computed(() => store.result?.result ?? null);

const sectorLabel = computed(() =>
  breakdown.value?.input.sector === 'industry' ? 'industria' : 'commercio',
);

const reliefsFromTax = computed(() => {
  const reliefs = breakdown.value?.reliefs;
  return reliefs ? reliefs.employmentRelief + reliefs.wedgeCutRelief : 0;
});

const allPayslips = computed(() => {
  const schedule = breakdown.value?.payslips;
  return schedule ? [schedule.ordinary, ...schedule.extras] : [];
});

/** The slices divide the RAL and nothing else. Exempt sums are stated apart, never here. */
const slices = computed(() => {
  const value = breakdown.value;
  if (!value) return [];

  const gross = value.grossAnnualSalary;
  const staysWithYou = gross - value.totalWithholdings;

  return [
    { label: 'Resta a te', amount: staysWithYou, share: staysWithYou / gross, color: '#7fb2ff' },
    {
      label: 'Contributi',
      amount: value.contributions.total,
      share: value.contributions.total / gross,
      color: '#3c6fc4',
    },
    { label: 'IRPEF', amount: value.netIrpef, share: value.netIrpef / gross, color: '#8e7cc8' },
    {
      label: 'Addizionali',
      amount: value.surtaxes.total,
      share: value.surtaxes.total / gross,
      color: '#c88ea8',
    },
  ];
});

const splitDescription = computed(() =>
  slices.value.map((slice) => `${slice.label}: ${formatEuro(slice.amount)}`).join('. '),
);

const payslipLabel = (kind: PayslipKind) => {
  switch (kind) {
    case 'thirteenth':
      return 'Tredicesima';
    case 'fourteenth':
      return 'Quattordicesima';
    default:
      return 'Busta ordinaria';
  }
};

async function copyLink() {
  await navigator.clipboard.writeText(window.location.href);
  copied.value = true;
  setTimeout(() => (copied.value = false), 2000);
}

onMounted(async () => {
  const token = route.params.token as string;

  // Reading by token rather than trusting whatever the wizard left in memory: the same page
  // then serves a shared link, and the snapshot is the source either way.
  if (store.result?.token === token) return;

  try {
    await store.loadByToken(token);
  } catch {
    failure.value = 'Questa simulazione non esiste, o è stata eliminata.';
  }
});
</script>

<style scoped lang="scss">
.result {
  min-height: 100dvh;
  background: var(--ink);
  padding: clamp(1rem, 3vh, 2rem) 1.25rem clamp(2rem, 6vh, 4rem);
}

.result__shell {
  width: min(52rem, 100%);
  margin-inline: auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.result__headline,
.result__split,
.result__detail,
.result__payslips {
  border-radius: 14px;
  padding: clamp(1.1rem, 3vw, 1.75rem);
}

.result__heading {
  margin: 0;
  font-size: var(--step-body);
  font-weight: 400;
  color: var(--muted);
}

.result__net {
  margin: 0.25rem 0 0;
  font-size: clamp(2.25rem, 1.5rem + 4vw, 3.5rem);
  line-height: 1.05;
  letter-spacing: -0.02em;
  font-weight: 700;
  color: var(--amber);
}

.result__per-month {
  margin: 0.5rem 0 0;
  color: var(--bone);
}

.result__muted {
  color: var(--muted);
}

.result__h2 {
  margin: 0 0 0.75rem;
  font-size: 1.125rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.result__note {
  margin: 0 0 1rem;
  color: var(--muted);
  font-size: var(--step-small);
  max-width: 62ch;
  text-wrap: pretty;
}

.result__exempt {
  margin: 1rem 0 0;
  font-size: var(--step-small);
  color: var(--bone);
  max-width: 62ch;
}

.split {
  display: flex;
  height: 14px;
  border-radius: 999px;
  overflow: hidden;
  gap: 2px;
}

.split__slice {
  height: 100%;
}

.legend {
  list-style: none;
  margin: 0.9rem 0 0;
  padding: 0;
  display: grid;
  gap: 0.4rem 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(13rem, 1fr));
}

.legend__item {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  font-size: var(--step-small);
}

.legend__swatch {
  width: 0.7rem;
  height: 0.7rem;
  border-radius: 3px;
  flex: none;
}

.legend__label {
  color: var(--bone);
}

.legend__amount {
  margin-left: auto;
  color: var(--muted);
}

.detail {
  width: 100%;
  border-collapse: collapse;
}

.result__sr {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}

.payslips {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
}

.payslip {
  background: var(--surface-raised);
  border: 1px solid var(--hairline);
  border-radius: 10px;
  padding: 0.85rem 1rem;
}

.payslip__kind {
  margin: 0;
  font-size: var(--step-small);
  color: var(--muted);
  font-weight: 400;
}

.payslip__net {
  margin: 0.2rem 0 0;
  font-size: 1.375rem;
  font-weight: 700;
}

.payslip__gross {
  margin: 0.1rem 0 0;
  font-size: 0.8125rem;
  color: var(--muted);
}

.result__assumptions {
  padding: 0 clamp(0.25rem, 2vw, 0.5rem);
}

.result__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.result__failure,
.result__loading {
  border-radius: 14px;
  padding: 1.5rem;
  color: var(--muted);
}
</style>
