<template>
  <div class="backdrop" aria-hidden="true">
    <Silk
      v-if="animate"
      :speed="3"
      :scale="1.2"
      :color="color"
      :noise-intensity="1.2"
      :rotation="0.1"
    />
    <div v-else class="backdrop__still" />
    <div class="backdrop__veil" />
  </div>
</template>

<script setup lang="ts">
import { onUnmounted, ref } from 'vue';
import Silk from '@/components/vendor/Silk.vue';

/**
 * The animated backdrop for the product's chrome: landing and login.
 *
 * Two things this wrapper owns that the vendored component does not:
 *
 * 1. Reduced motion. A WebGL loop is exactly the kind of continuous animation
 *    prefers-reduced-motion exists to stop, and a CSS media query cannot reach inside a canvas.
 *    The whole component is swapped for a static gradient rather than merely slowed, so no
 *    render loop is started at all — which also spares the battery on the machines most likely
 *    to have the setting turned on.
 * 2. The palette and the veil, kept here so the vendored file stays verbatim and re-syncable.
 */

const color = '#2a4a86';

const query = window.matchMedia('(prefers-reduced-motion: reduce)');
const animate = ref(!query.matches);

const onChange = (event: MediaQueryListEvent) => {
  animate.value = !event.matches;
};

query.addEventListener('change', onChange);
onUnmounted(() => query.removeEventListener('change', onChange));
</script>

<style scoped lang="scss">
.backdrop {
  position: absolute;
  inset: 0;
  z-index: var(--z-backdrop);
  overflow: hidden;
  background: var(--ink);
}

/* What reduced-motion readers get: the same composition, holding still. */
.backdrop__still {
  position: absolute;
  inset: 0;
  background: radial-gradient(130% 90% at 50% 0%, rgba(60, 111, 196, 0.55), transparent 62%);
}

/*
 * Silk is brightest in its mid-tones, which is exactly where the headline sits. This veil is
 * what keeps the measured ratios honest at run time rather than only in the token table.
 */
.backdrop__veil {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(8, 12, 20, 0.74) 0%,
    rgba(8, 12, 20, 0.56) 45%,
    rgba(8, 12, 20, 0.93) 100%
  );
}
</style>
