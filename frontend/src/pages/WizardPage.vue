<template>
  <div class="wizard">
    <div class="wizard__shell">
      <AppHeader />

      <q-stepper
        v-model="step"
        animated
        flat
        alternative-labels
        dark
        color="primary"
        class="wizard__stepper panel-solid"
        :contracted="$q.screen.lt.sm"
      >
        <q-step :name="1" title="Stipendio" icon="payments" :done="step > 1">
          <h1 class="wizard__question">Quanto c'è scritto sul contratto?</h1>
          <p class="wizard__hint">
            La RAL è il totale lordo annuo, prima di contributi e imposte. È il numero grande
            dell'offerta, non quello che arriva in banca.
          </p>

          <q-input
            ref="salaryInput"
            v-model.number="store.grossAnnualSalary"
            dark
            outlined
            type="number"
            inputmode="decimal"
            autofocus
            suffix="€"
            class="wizard__field tabular"
            label="RAL lorda annua"
            :rules="[salaryRule]"
            @keyup.enter="next"
          />
        </q-step>

        <q-step :name="2" title="Mensilità" icon="event_repeat" :done="step > 2">
          <h1 class="wizard__question">In quante mensilità?</h1>
          <p class="wizard__hint">
            La 13ª e la 14ª non sono soldi in più: la stessa RAL si divide in più buste. Rendono
            anche meno di una busta ordinaria, e il risultato lo mostra.
          </p>

          <q-option-group
            v-model="store.monthlyPaymentsCount"
            dark
            type="radio"
            color="primary"
            class="wizard__options"
            :options="paymentOptions"
          />
        </q-step>

        <q-step :name="3" title="Settore" icon="factory" :done="step > 3">
          <h1 class="wizard__question">Commercio o industria?</h1>
          <p class="wizard__hint">
            Cambia l'aliquota dei contributi a tuo carico: l'industria versa in più lo 0,30% per la
            cassa integrazione. Sono circa cento euro l'anno su una RAL da 35.000.
          </p>

          <q-option-group
            v-model="store.sector"
            dark
            type="radio"
            color="primary"
            class="wizard__options"
            :options="sectorOptions"
          />
        </q-step>

        <q-step :name="4" title="Luogo" icon="location_city" :done="step > 4">
          <h1 class="wizard__question">Dove hai la residenza fiscale?</h1>
          <p class="wizard__hint">
            Decide le addizionali regionale e comunale. Sono elencate solo le città di cui sono
            state verificate entrambe le aliquote sulle fonti ufficiali.
          </p>

          <q-select
            v-model="store.municipality"
            dark
            outlined
            emit-value
            map-options
            class="wizard__field"
            label="Comune"
            :options="municipalityOptions"
            :loading="loadingPlaces"
          />

          <p v-if="failure" class="wizard__error" role="alert">{{ failure }}</p>
        </q-step>
      </q-stepper>

      <nav class="wizard__nav" aria-label="Navigazione del percorso">
        <q-btn v-if="step > 1" flat no-caps dark color="primary" label="Indietro" @click="step--" />
        <span class="wizard__spacer" />
        <q-btn
          unelevated
          no-caps
          color="primary"
          text-color="dark"
          class="wizard__next"
          :label="step === 4 ? 'Calcola il netto' : 'Avanti'"
          :disable="!canAdvance"
          :loading="store.running"
          @click="next"
        />
      </nav>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useQuasar } from 'quasar';
import { useRouter } from 'vue-router';
import AppHeader from '@/components/AppHeader.vue';
import { useSimulationStore } from '@/stores/simulation';
import { ApiError } from '@/services/api';

/**
 * Four questions, one per screen, in the order the payslip applies them.
 *
 * No animated backdrop here: from this screen on the numbers are the content, and
 * docs/PRODOTTO.md draws the line at the CTA. The stepper sits on a solid panel for the same
 * reason — figures are read here, and glass costs contrast.
 *
 * No arithmetic either. The four answers go to the API and the engine returns the breakdown.
 */

const $q = useQuasar();
const router = useRouter();
const store = useSimulationStore();

const step = ref(1);
const failure = ref<string | null>(null);
const loadingPlaces = ref(false);

const paymentOptions = [
  { label: '12 mensilità', value: 12 },
  { label: '13 mensilità, con la tredicesima', value: 13 },
  { label: '14 mensilità, con tredicesima e quattordicesima', value: 14 },
];

const sectorOptions = [
  { label: 'Commercio', value: 'commerce' },
  { label: 'Industria', value: 'industry' },
];

const municipalityOptions = computed(() =>
  store.municipalities.map((place) => ({
    label: `${place.name} (${place.province}) · ${place.region}`,
    value: place.name,
  })),
);

const salaryRule = (value: number | null) =>
  (value !== null && value > 0) || 'Inserisci una RAL maggiore di zero.';

const canAdvance = computed(() => {
  if (step.value === 1) return (store.grossAnnualSalary ?? 0) > 0;
  if (step.value === 4) return store.municipality !== null;
  return true;
});

onMounted(async () => {
  loadingPlaces.value = true;
  try {
    await store.loadMunicipalities();
  } finally {
    loadingPlaces.value = false;
  }
});

async function next() {
  if (!canAdvance.value) return;

  if (step.value < 4) {
    step.value += 1;
    return;
  }

  failure.value = null;
  try {
    const simulation = await store.run();
    await router.push(`/risultato/${simulation.token}`);
  } catch (error) {
    // The server validates too, and its message is the more precise one: show that rather than
    // a generic failure, so a rejected field says which and why.
    failure.value =
      error instanceof ApiError
        ? (error.fieldError('municipality') ??
          error.fieldError('gross_annual_salary') ??
          error.message)
        : 'Calcolo non riuscito. Riprova.';
  }
}
</script>

<style scoped lang="scss">
.wizard {
  min-height: 100dvh;
  background: var(--ink);
  padding: clamp(1rem, 3vh, 2rem) 1.25rem;
}

.wizard__shell {
  width: min(46rem, 100%);
  margin-inline: auto;
  display: flex;
  flex-direction: column;
  gap: clamp(1rem, 3vh, 1.75rem);
}

.wizard__stepper {
  border-radius: 14px;
  background: var(--surface) !important;
}

.wizard__question {
  margin: 0 0 0.5rem;
  font-size: clamp(1.4rem, 1.1rem + 1.4vw, 2rem);
  line-height: 1.2;
  letter-spacing: -0.015em;
  font-weight: 700;
  text-wrap: balance;
}

.wizard__hint {
  margin: 0 0 1.5rem;
  color: var(--muted);
  max-width: 58ch;
  text-wrap: pretty;
}

.wizard__field {
  max-width: 26rem;
  font-size: 1.125rem;
}

.wizard__options {
  font-size: var(--step-body);
}

.wizard__error {
  margin: 1rem 0 0;
  color: #e08a7d;
  font-weight: 700;
}

.wizard__nav {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.wizard__spacer {
  flex: 1;
}

.wizard__next {
  font-weight: 700;
  padding: 0.6rem 1.5rem;
  border-radius: 10px;
}
</style>
