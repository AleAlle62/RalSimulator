<template>
  <main class="saved">
    <SilkBackdrop fixed />

    <div class="saved__shell">
      <AppHeader />

      <h1 class="saved__title">Le mie simulazioni</h1>

      <p v-if="loading" class="saved__note">Carico…</p>

      <p v-else-if="!auth.isAuthenticated" class="saved__signin glass-panel">
        Serve un account per ritrovare le simulazioni salvate.
        <router-link to="/accedi" class="saved__link">Accedi</router-link>
      </p>

      <div v-else-if="simulations.length === 0" class="saved__empty glass-panel">
        <p class="saved__note">
          Non hai ancora salvato niente. Ogni calcolo che fai da qui in avanti finisce in questo
          elenco.
        </p>
        <q-btn
          unelevated
          no-caps
          color="primary"
          text-color="dark"
          label="Fai un calcolo"
          to="/simulazione"
        />
      </div>

      <ul v-else class="saved__list">
        <li v-for="item in simulations" :key="item.id" class="saved__item glass-panel">
          <router-link :to="`/risultato/${item.token}`" class="saved__main">
            <span class="saved__net tabular">{{ formatEuro(item.result.netAnnualSalary) }}</span>
            <span class="saved__meta">
              su {{ formatEuro(item.grossAnnualSalary) }} lordi · {{ item.municipality }} ·
              {{ item.monthlyPaymentsCount }} mensilità
            </span>
            <span class="saved__date">{{ formatDate(item.createdAt) }}</span>
          </router-link>

          <q-btn
            flat
            dense
            no-caps
            dark
            color="primary"
            :label="confirmingId === item.id ? 'Confermi?' : 'Elimina'"
            :aria-label="`Elimina la simulazione da ${formatEuro(item.result.netAnnualSalary)}`"
            @click="requestDelete(item.id)"
          />
        </li>
      </ul>
    </div>
  </main>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import AppHeader from '@/components/AppHeader.vue';
import SilkBackdrop from '@/components/SilkBackdrop.vue';
import { useCurrency } from '@/composables/useCurrency';
import { useAuthStore } from '@/stores/auth';
import { useSimulationStore } from '@/stores/simulation';
import type { Simulation } from '@/types/simulation';

/**
 * The signed-in user's saved simulations, in the same glass as the wizard and the result.
 *
 * Deleting asks twice, on the button itself rather than in a dialog. The row is small and the
 * action is irreversible, so a second deliberate click is proportionate where a modal would be
 * ceremony.
 */

const auth = useAuthStore();
const store = useSimulationStore();
const { formatEuro } = useCurrency();

const simulations = ref<Simulation[]>([]);
const loading = ref(true);
const confirmingId = ref<number | null>(null);

const formatDate = (iso: string) =>
  new Date(iso).toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' });

async function load() {
  loading.value = true;
  try {
    simulations.value = await store.listMine();
  } catch {
    simulations.value = [];
  } finally {
    loading.value = false;
  }
}

async function requestDelete(id: number) {
  if (confirmingId.value !== id) {
    confirmingId.value = id;
    setTimeout(() => {
      if (confirmingId.value === id) confirmingId.value = null;
    }, 4000);
    return;
  }

  confirmingId.value = null;
  await store.remove(id);
  simulations.value = simulations.value.filter((item) => item.id !== id);
}

onMounted(load);
</script>

<style scoped lang="scss">
.saved {
  position: relative;
  min-height: 100dvh;
  display: flex;
}

.saved__shell {
  position: relative;
  z-index: var(--z-content);
  width: min(48rem, 100% - 2.5rem);
  margin-inline: auto;
  padding-block: clamp(1rem, 3vh, 2rem) clamp(2rem, 6vh, 4rem);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.saved__title {
  margin: 0.5rem 0 0;
  font-size: clamp(1.5rem, 1.2rem + 1.4vw, 2rem);
  letter-spacing: -0.015em;
  font-weight: 700;
}

.saved__note {
  margin: 0;
  color: var(--muted);
}

.saved__link {
  color: var(--azure);
  font-weight: 700;
}

.saved__signin,
.saved__empty,
.saved__item {
  border-radius: 12px;
  padding: 1rem 1.15rem;
}

.saved__signin {
  margin: 0;
  color: var(--muted);
}

.saved__empty {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.saved__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.saved__item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.saved__main {
  flex: 1;
  display: grid;
  gap: 0.1rem;
  text-decoration: none;
  color: inherit;
}

.saved__net {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--amber);
}

.saved__meta {
  font-size: var(--step-small);
  color: var(--bone);
}

.saved__date {
  font-size: 0.8125rem;
  color: var(--muted);
}
</style>
