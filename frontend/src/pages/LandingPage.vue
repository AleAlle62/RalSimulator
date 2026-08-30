<template>
  <main class="landing">
    <SilkBackdrop />

    <div class="landing__content">
      <AppHeader />

      <div class="landing__middle">
        <h1 class="landing__title">
          Sul contratto c'è scritto <span class="landing__gross tabular">35.000&nbsp;€</span>.<br />
          In banca ne arrivano
          <span class="landing__net tabular">{{ displayedNet }}&nbsp;€</span>.
        </h1>

        <p class="landing__lead">
          La differenza sono contributi, IRPEF e addizionali, più due somme esenti che quasi nessun
          calcolatore conta. Questo simulatore le mostra una per una, con accanto la norma da cui
          vengono.
        </p>

        <div class="landing__actions">
          <q-btn
            unelevated
            no-caps
            color="primary"
            text-color="dark"
            class="landing__cta"
            label="Calcola il tuo netto"
            to="/simulazione"
          />
          <p class="landing__reassure">
            Nessuna registrazione, nessun dato salvato finché non lo chiedi tu.
          </p>
        </div>
      </div>

      <footer class="landing__foot glass">
        <dl class="landing__facts">
          <div class="landing__fact">
            <dt>Fonti</dt>
            <dd>Ogni aliquota è archiviata con la norma e il link che la stabilisce.</dd>
          </div>
          <div class="landing__fact">
            <dt>Mensilità</dt>
            <dd>13ª e 14ª calcolate davvero: rendono meno di una busta ordinaria.</dd>
          </div>
        </dl>

        <div class="landing__author">
          <span class="landing__by">Alessio Allegrini</span>
          <ul class="landing__links">
            <li v-for="link in authorLinks" :key="link.href">
              <a :href="link.href" target="_blank" rel="noopener noreferrer">{{ link.label }}</a>
            </li>
          </ul>
        </div>
      </footer>
    </div>
  </main>
</template>

<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue';
import SilkBackdrop from '@/components/SilkBackdrop.vue';
import { useCountUp } from '@/composables/useCountUp';

/**
 * The headline is the product's argument in one sentence: a number everybody recognises, and
 * the number that actually arrives. Both are real output from the engine for 35.000 € gross,
 * commerce, 14 payments — the same figure the backend test asserts, so the landing cannot
 * quietly drift away from what the calculator returns.
 */
const { displayed: displayedNet } = useCountUp(25967.22, { durationMs: 1100 });

/** Link text carries its own meaning: screen readers announce these out of context. */
const authorLinks = [
  { label: 'Sito', href: 'https://alessioallegrini.it' },
  { label: 'GitHub', href: 'https://github.com/AleAlle62' },
  { label: 'LinkedIn', href: 'https://www.linkedin.com/in/alessio-allegrini00/' },
];
</script>

<style scoped lang="scss">
.landing {
  position: relative;
  min-height: 100dvh;
  /* One screen, no scroll: the constraint in docs/PRODOTTO.md. If the copy stops fitting, the
     copy gets cut, not the constraint. */
  overflow: hidden;
  display: flex;
}

.landing__content {
  position: relative;
  z-index: var(--z-content);
  width: min(72rem, 100% - 2.5rem);
  margin-inline: auto;
  padding-block: clamp(1rem, 3vh, 2rem);
  display: grid;
  grid-template-rows: auto 1fr auto;
  gap: clamp(1rem, 3vh, 2.25rem);
}

.landing__middle {
  align-self: center;
  max-width: 46rem;
}

.landing__title {
  margin: 0;
  font-size: var(--step-display);
  line-height: 1.08;
  /* Floor is -0.04em; this stays well inside it so the letters never touch. */
  letter-spacing: -0.02em;
  font-weight: 700;
  text-wrap: balance;
}

.landing__gross {
  color: var(--muted);
  text-decoration: line-through;
  text-decoration-thickness: 2px;
  text-decoration-color: var(--azure-deep);
}

.landing__net {
  color: var(--amber);
}

.landing__lead {
  margin: 1.5rem 0 0;
  font-size: var(--step-lead);
  line-height: 1.55;
  color: var(--bone);
  max-width: 60ch;
  text-wrap: pretty;
}

.landing__actions {
  margin-top: clamp(1.25rem, 3vh, 2.25rem);
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem 1.5rem;
}

.landing__cta {
  font-size: 1.0625rem;
  font-weight: 700;
  padding: 0.75rem 1.75rem;
  border-radius: 10px;
  transition: transform 220ms var(--ease-out-quint);

  &:hover {
    transform: translateY(-2px);
  }
}

.landing__reassure {
  margin: 0;
  font-size: var(--step-small);
  color: var(--muted);
}

.landing__foot {
  border-radius: 12px;
  padding: 0.9rem 1.1rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem 2rem;
}

.landing__facts {
  margin: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 2.5rem;
}

.landing__fact {
  max-width: 32ch;

  dt {
    font-size: var(--step-small);
    font-weight: 700;
    color: var(--azure);
  }

  dd {
    margin: 0;
    font-size: var(--step-small);
    line-height: 1.4;
    color: var(--muted);
  }
}

.landing__author {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.landing__by {
  font-size: var(--step-small);
  color: var(--muted);
}

.landing__links {
  display: flex;
  gap: 0.85rem;
  margin: 0;
  padding: 0;
  list-style: none;

  a {
    font-size: var(--step-small);
    font-weight: 700;
    color: var(--azure);
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: border-color 180ms var(--ease-out-quint);

    &:hover {
      border-bottom-color: var(--azure);
    }
  }
}

/* Below the fold is not an option here, so the short-viewport case is designed rather than left
   to chance: the supporting facts go before the headline or the author credit do. */
@media (max-height: 700px) {
  .landing__lead {
    font-size: var(--step-body);
    margin-top: 1rem;
  }

  .landing__facts {
    display: none;
  }
}
</style>
