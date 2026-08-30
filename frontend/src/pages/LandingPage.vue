<template>
  <main class="landing">
    <AuroraBackdrop />

    <div class="landing__content">
      <header class="landing__head">
        <p class="landing__year">Anno d'imposta 2026</p>
      </header>

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

      <footer class="landing__foot">
        <dl class="landing__facts">
          <div class="landing__fact">
            <dt>Fonti</dt>
            <dd>Ogni aliquota è archiviata con la norma e il link che la stabilisce.</dd>
          </div>
          <div class="landing__fact">
            <dt>Mensilità</dt>
            <dd>13ª e 14ª calcolate davvero: rendono meno di una busta ordinaria.</dd>
          </div>
          <div class="landing__fact">
            <dt>Limiti</dt>
            <dd>Lavoro dipendente, senza familiari a carico. Dichiarati, non nascosti.</dd>
          </div>
        </dl>
      </footer>
    </div>
  </main>
</template>

<script setup lang="ts">
import AuroraBackdrop from '@/components/AuroraBackdrop.vue';
import { useCountUp } from '@/composables/useCountUp';

/**
 * The headline is the product's argument in one sentence: a number everybody recognises, and
 * the number that actually arrives. Both are real output from the engine for 35.000 € gross,
 * commerce, 14 payments — the same figure the backend test asserts, so the landing cannot
 * quietly drift away from what the calculator returns.
 */
const { displayed: displayedNet } = useCountUp(25967.22, { durationMs: 1100 });
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
  width: min(72rem, 100% - 3rem);
  margin-inline: auto;
  padding-block: clamp(1.5rem, 4vh, 3rem);
  display: grid;
  grid-template-rows: auto 1fr auto;
  gap: clamp(1.5rem, 4vh, 3rem);
}

.landing__year {
  margin: 0;
  font-size: var(--step-small);
  color: var(--moss);
  font-weight: 600;
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
  text-decoration-color: var(--moss-deep);
}

.landing__net {
  color: var(--gold);
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
  margin-top: clamp(1.5rem, 3vh, 2.25rem);
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
  border-top: 1px solid var(--hairline);
  padding-top: clamp(1rem, 2.5vh, 1.5rem);
}

.landing__facts {
  margin: 0;
  display: grid;
  gap: 1rem 2.5rem;
  grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr));
}

.landing__fact {
  dt {
    font-size: var(--step-small);
    font-weight: 700;
    color: var(--moss);
    margin-bottom: 0.15rem;
  }

  dd {
    margin: 0;
    font-size: var(--step-small);
    line-height: 1.45;
    color: var(--muted);
  }
}

/* Below the fold is not an option here, so the short-viewport case is designed, not left to
   chance: the supporting text goes before the headline does. */
@media (max-height: 640px) {
  .landing__lead {
    font-size: var(--step-body);
    margin-top: 1rem;
  }

  .landing__foot {
    display: none;
  }
}
</style>
