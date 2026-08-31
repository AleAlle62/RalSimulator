<template>
  <main class="result">
    <SilkBackdrop fixed />

    <div class="result__content">
      <AppHeader />

      <template v-if="breakdown">
        <section class="hero glass-panel" aria-labelledby="net-heading">
          <p class="hero__context">
            Su <strong class="tabular">{{ formatEuro(breakdown.grossAnnualSalary) }}</strong> lordi
            <span class="hero__where">
              · {{ simulation?.municipality }} · {{ sectorLabel }} ·
              {{ breakdown.input.monthlyPaymentsCount }} mensilità
            </span>
          </p>

          <h1 id="net-heading" class="hero__heading">Ti restano</h1>
          <p class="hero__net tabular">{{ formatEuro(breakdown.netAnnualSalary) }}</p>
          <p class="hero__unit">netti all'anno</p>

          <dl class="hero__stats">
            <div class="stat">
              <dt class="stat__label">In una busta ordinaria</dt>
              <dd class="stat__value tabular">
                {{ formatEuro(breakdown.payslips.ordinary.net) }}
              </dd>
            </div>
            <div class="stat">
              <dt class="stat__label">Trattenute in un anno</dt>
              <dd class="stat__value tabular">
                {{ formatEuro(breakdown.totalWithholdings) }}
              </dd>
            </div>
          </dl>
        </section>

        <section class="split glass-panel" aria-labelledby="split-heading">
          <h2 id="split-heading" class="section__title">Dove finisce il lordo</h2>
          <p class="section__note">
            La barra divide la RAL e nient'altro: le quattro voci sommano esattamente
            {{ formatEuro(breakdown.grossAnnualSalary) }}.
          </p>

          <div class="bar" role="img" :aria-label="splitDescription">
            <span
              v-for="slice in slices"
              :key="slice.label"
              class="bar__slice"
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
              <span class="legend__share tabular">{{ formatPercent(slice.share) }}</span>
              <span class="legend__amount tabular">{{ formatEuro(slice.amount) }}</span>
            </li>
          </ul>

          <p v-if="breakdown.taxFreeAdditions > 0" class="split__exempt">
            <span class="split__operator" aria-hidden="true">+</span>
            <span>
              A questo si <strong>aggiungono</strong>
              <strong class="tabular">{{ formatEuro(breakdown.taxFreeAdditions) }}</strong>
              di somme esenti, che non escono dal lordo: per questo il netto non è semplicemente una
              fetta della barra qui sopra.
            </span>
          </p>
        </section>

        <section class="payslips glass-panel" aria-labelledby="payslips-heading">
          <h2 id="payslips-heading" class="section__title">Le buste</h2>
          <p class="section__note">
            Le mensilità aggiuntive rendono meno di una ordinaria a parità di lordo: non portano
            detrazioni né addizionali, che sono già state applicate sulle buste ordinarie.
          </p>

          <div class="payslips__grid">
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

        <q-expansion-item
          dark
          class="detail glass-panel"
          header-class="detail__header"
          label="Riga per riga"
          caption="Ogni trattenuta con il suo importo, dal lordo al netto"
          icon="receipt_long"
        >
          <div class="detail__body">
            <table class="detail__table">
              <caption class="sr-only">
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
                <BreakdownRow
                  label="Netto annuo"
                  :amount="breakdown.netAnnualSalary"
                  sign="equals"
                />
              </tbody>
            </table>
          </div>
        </q-expansion-item>

        <section class="keep glass-panel" aria-labelledby="keep-heading">
          <h2 id="keep-heading" class="section__title">Tenere questo risultato</h2>

          <p v-if="simulation?.mine" class="keep__state">
            <span class="keep__mark" aria-hidden="true">✓</span>
            <span>
              È nelle tue simulazioni. La ritrovi da
              <router-link to="/simulazioni" class="keep__link">Le mie simulazioni</router-link>,
              con i numeri congelati a oggi.
            </span>
          </p>

          <p v-else class="section__note keep__note">
            Il link qui sotto funziona già e non scade: chi lo apre vede questi stessi numeri, non
            un ricalcolo.
            <template v-if="!auth.isAuthenticated">
              Con un account la ritrovi anche senza tenerti il link da parte.
            </template>
          </p>

          <div class="keep__actions">
            <q-btn
              unelevated
              no-caps
              color="primary"
              text-color="dark"
              class="keep__cta"
              :label="copied ? 'Link copiato' : 'Copia il link'"
              @click="copyLink"
            />

            <q-btn
              v-if="!auth.isAuthenticated"
              flat
              no-caps
              dark
              color="primary"
              label="Accedi per salvarla"
              :to="`/accedi?ritorna=${route.fullPath}`"
            />

            <q-btn
              v-else-if="simulation?.claimable"
              flat
              no-caps
              dark
              color="primary"
              label="Salva nelle mie simulazioni"
              :loading="saving"
              @click="save"
            />
          </div>

          <p v-if="copyFailure" class="keep__error" role="alert">{{ copyFailure }}</p>
          <p v-if="saveFailure" class="keep__error" role="alert">{{ saveFailure }}</p>
        </section>

        <section class="assumptions">
          <h2 class="section__title">Cosa non è compreso</h2>
          <p class="section__note">
            Lavoro dipendente, nessun familiare a carico, nessun onere detraibile, nessun TFR. Il
            costo per l'azienda non è calcolato: qui c'è solo la quota a tuo carico. Non sostituisce
            il conteggio di un consulente del lavoro.
          </p>

          <div class="assumptions__actions">
            <q-btn flat no-caps dark color="primary" label="Rifai il calcolo" to="/simulazione" />
          </div>
        </section>
      </template>

      <p v-else-if="failure" class="result__failure glass-panel" role="alert">{{ failure }}</p>
      <p v-else class="result__loading">Carico la simulazione…</p>
    </div>
  </main>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute } from 'vue-router';
import AppHeader from '@/components/AppHeader.vue';
import BreakdownRow from '@/components/BreakdownRow.vue';
import SilkBackdrop from '@/components/SilkBackdrop.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useAuthStore } from '@/stores/auth';
import { useSimulationStore } from '@/stores/simulation';
import type { PayslipKind } from '@/types/simulation';

/**
 * The screen the reviewer checks and the screen the employee learns from.
 *
 * The order answers the employee's questions first — how much, how much a month, where the rest
 * went — and puts the reviewer's line-by-line audit behind a disclosure. That is the progressive
 * disclosure the README asks for, not a retreat from it: the split and its legend
 * are on screen unopened, so no total is ever shown without its decomposition.
 *
 * The one thing this page must not get wrong is the exempt sums. They are not a slice of the
 * gross, they are added to the net, so the bar divides the RAL only and they are stated
 * separately underneath with a `+`. Folding them into "resta a te" would make the bar promise
 * more than the whole — which is also why the legend shows a share for the four slices and never
 * one for the exempt line.
 */

const route = useRoute();
const auth = useAuthStore();
const store = useSimulationStore();
const { formatEuro, formatPercent } = useCurrency();

const failure = ref<string | null>(null);
const copied = ref(false);
const copyFailure = ref<string | null>(null);
const saving = ref(false);
const saveFailure = ref<string | null>(null);

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

/**
 * Copia /s/{token}, non l'indirizzo corrente.
 *
 * Le due pagine mostrano la stessa simulazione, ma solo /s/{token} è renderizzata dal server:
 * incollata in una chat o in una mail porta con sé i meta OpenGraph con il netto, mentre
 * /risultato/{token} è la SPA e per un crawler è una pagina vuota. Chi apre il link atterra
 * comunque sul risultato completo, dal pulsante che quella pagina contiene.
 *
 * writeText può fallire — appunti negati, contesto non sicuro, Safari fuori da un gesto
 * dell'utente. Senza il catch la promise resterebbe non gestita e il pulsante non direbbe nulla:
 * l'utente crederebbe di avere un link negli appunti e non ce l'avrebbe.
 */
async function copyLink() {
  const link = new URL(`/s/${route.params.token as string}`, window.location.origin).toString();

  copyFailure.value = null;

  try {
    await navigator.clipboard.writeText(link);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
  } catch {
    copyFailure.value = `Il browser non mi ha lasciato usare gli appunti. Il link è ${link}`;
  }
}

async function save() {
  saving.value = true;
  saveFailure.value = null;

  try {
    await store.claim(route.params.token as string);
  } catch {
    saveFailure.value = 'Non sono riuscito a salvarla. Riprova.';
  } finally {
    saving.value = false;
  }
}

onMounted(async () => {
  const token = route.params.token as string;

  // Reading by token rather than trusting whatever the wizard left in memory: the same page
  // then serves a shared link, and the snapshot is the source either way.
  //
  // The copy in memory is reused only while its ownership flags can still be right. They are
  // computed for whoever asked, so the copy fetched as a guest says `mine: false` forever —
  // including right after signing in and coming back here, which is exactly when the page has
  // to offer to save it.
  const flagsCouldBeStale = auth.isAuthenticated && store.result?.mine === false;
  if (store.result?.token === token && !flagsCouldBeStale) return;

  try {
    await store.loadByToken(token);
  } catch {
    failure.value = 'Questa simulazione non esiste, o è stata eliminata.';
  }
});
</script>

<style scoped lang="scss">
.result {
  position: relative;
  min-height: 100dvh;
  display: flex;
}

.result__content {
  position: relative;
  z-index: var(--z-content);
  width: min(52rem, 100% - 2.5rem);
  margin-inline: auto;
  padding-block: clamp(1rem, 3vh, 2rem) clamp(2rem, 6vh, 4rem);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.hero,
.split,
.payslips,
.keep,
.detail,
.result__failure {
  border-radius: 16px;
}

.hero,
.split,
.payslips,
.keep {
  padding: clamp(1.25rem, 3.5vw, 2rem);
}

/* The headline block. One figure is allowed to be loud here; everything under it is quiet, which
   is what keeps this from becoming the SaaS hero the README lists as an anti-reference. */
.hero__context {
  margin: 0;
  color: var(--muted);
  font-size: var(--step-small);

  strong {
    color: var(--bone);
    font-weight: 700;
  }
}

.hero__where {
  white-space: nowrap;
}

.hero__heading {
  margin: 0.9rem 0 0;
  font-size: var(--step-body);
  font-weight: 400;
  color: var(--muted);
}

.hero__net {
  margin: 0.1rem 0 0;
  font-size: clamp(2.5rem, 1.4rem + 5.5vw, 4.25rem);
  line-height: 1;
  letter-spacing: -0.025em;
  font-weight: 700;
  color: var(--amber);
}

.hero__unit {
  margin: 0.35rem 0 0;
  color: var(--muted);
  font-size: var(--step-small);
}

.hero__stats {
  margin: clamp(1.25rem, 3vw, 1.75rem) 0 0;
  padding-top: clamp(1rem, 3vw, 1.5rem);
  border-top: 1px solid var(--hairline);
  display: grid;
  gap: 1rem 2rem;
  grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
}

.stat__label {
  color: var(--muted);
  font-size: var(--step-small);
}

.stat__value {
  margin: 0.15rem 0 0;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.15;
}

.section__title {
  margin: 0 0 0.4rem;
  font-size: 1.125rem;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.section__note {
  margin: 0 0 1.25rem;
  color: var(--muted);
  font-size: var(--step-small);
  max-width: 62ch;
  text-wrap: pretty;
}

/* Twice the old height. At 14px the four slices read as a rule under the heading; at 28px the
   proportion is the thing you see first, which is the whole job of this section. */
.bar {
  display: flex;
  height: 28px;
  border-radius: 999px;
  overflow: hidden;
  gap: 2px;
}

.bar__slice {
  height: 100%;
}

.legend {
  list-style: none;
  margin: 1.1rem 0 0;
  padding: 0;
  display: grid;
  gap: 0.15rem;
}

/* A four-column grid rather than four flex rows: the shares line up under each other and the
   amounts end on the same right edge, so the column can be read down instead of item by item. */
.legend__item {
  display: grid;
  grid-template-columns: 0.7rem 1fr auto auto;
  align-items: baseline;
  gap: 0.65rem;
  font-size: var(--step-small);
  padding: 0.35rem 0;
  border-bottom: 1px solid var(--hairline);

  &:last-child {
    border-bottom: none;
  }
}

.legend__swatch {
  width: 0.7rem;
  height: 0.7rem;
  border-radius: 3px;
}

.legend__label {
  color: var(--bone);
}

.legend__share {
  color: var(--muted);
  min-width: 4.5rem;
  text-align: right;
}

.legend__amount {
  color: var(--bone);
  min-width: 7rem;
  text-align: right;
}

.split__exempt {
  margin: 1.25rem 0 0;
  padding: 0.85rem 1rem;
  border: 1px solid var(--hairline);
  border-radius: 10px;
  background: rgba(127, 178, 255, 0.06);
  font-size: var(--step-small);
  color: var(--bone);
  display: flex;
  gap: 0.7rem;
  align-items: baseline;
  text-wrap: pretty;
}

.split__operator {
  color: var(--azure);
  font-weight: 700;
  font-size: 1.25rem;
  line-height: 1;
  flex: none;
}

.payslips__grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
}

/* Glass built from layers, not from backdrop-filter. These cards sit inside .glass-panel,
   whose tint is 0.93 opaque: there is nothing left behind them to refract, so a nested blur
   would buy a compositing layer and show nothing. What reads as glass here is the diagonal
   sheen, the lit top edge and the shadow that lifts the card off the panel. */
.payslip {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  border: 1px solid var(--glass-edge);
  border-radius: 14px;
  padding: 1rem 1.1rem;
  background:
    linear-gradient(
      158deg,
      rgba(232, 238, 247, 0.1) 0%,
      rgba(232, 238, 247, 0.03) 38%,
      rgba(232, 238, 247, 0) 64%
    ),
    var(--surface-raised);
  box-shadow:
    inset 0 1px 0 rgba(232, 238, 247, 0.2),
    inset 0 -1px 0 rgba(8, 12, 20, 0.35),
    0 10px 24px -14px rgba(8, 12, 20, 0.9);
}

/* The specular pool in the top corner: the detail that makes the surface read as curved glass
   rather than as a flat tinted rectangle. Behind the text, above the base gradient. */
.payslip::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: -1;
  background: radial-gradient(120% 90% at 8% -12%, rgba(232, 238, 247, 0.14), transparent 58%);
  pointer-events: none;
}

/* The extras stay marked, by the tint of their glass. The distinction does not rest on colour
   alone: "Tredicesima" and "Quattordicesima" name them, and the note above the grid says what
   makes them different. */
.payslip--extra {
  background:
    linear-gradient(
      158deg,
      rgba(127, 178, 255, 0.14) 0%,
      rgba(127, 178, 255, 0.04) 40%,
      rgba(232, 238, 247, 0) 66%
    ),
    var(--surface-raised);
}

.payslip--extra::before {
  background: radial-gradient(120% 90% at 8% -12%, rgba(127, 178, 255, 0.18), transparent 58%);
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

.detail {
  overflow: hidden;

  :deep(.detail__header) {
    padding: clamp(0.9rem, 3vw, 1.25rem) clamp(1.25rem, 3.5vw, 2rem);
  }

  :deep(.q-item__label) {
    font-size: 1.125rem;
    font-weight: 700;
    letter-spacing: -0.01em;
  }

  :deep(.q-item__label--caption) {
    font-size: var(--step-small);
    font-weight: 400;
    letter-spacing: 0;
    color: var(--muted);
  }
}

.detail__body {
  padding: 0 clamp(1.25rem, 3.5vw, 2rem) clamp(1rem, 3vw, 1.5rem);
}

.detail__table {
  width: 100%;
  border-collapse: collapse;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}

.keep__note {
  margin-bottom: 1.1rem;
}

.keep__state {
  margin: 0 0 1.1rem;
  display: flex;
  gap: 0.6rem;
  align-items: baseline;
  font-size: var(--step-small);
  color: var(--bone);
  max-width: 62ch;
  text-wrap: pretty;
}

/* The tick is decoration: the sentence next to it already says the simulation is saved, so a
   screen reader that never sees this character loses nothing. */
.keep__mark {
  color: var(--azure);
  font-weight: 700;
  flex: none;
}

.keep__link {
  color: var(--azure);
  font-weight: 700;
}

.keep__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 0.75rem;
}

.keep__cta {
  font-weight: 700;
  padding: 0.6rem 1.5rem;
  border-radius: 10px;
}

.keep__error {
  margin: 0.9rem 0 0;
  color: #e08a7d;
  font-weight: 700;
  font-size: var(--step-small);
}

.assumptions {
  padding: 0.5rem clamp(0.25rem, 2vw, 0.5rem) 0;
}

.assumptions__actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 0.75rem;
}

.assumptions__cta {
  font-weight: 700;
  padding: 0.6rem 1.5rem;
  border-radius: 10px;
}

.result__failure,
.result__loading {
  padding: 1.5rem;
  color: var(--muted);
}
</style>
