<template>
  <div class="backdrop" aria-hidden="true">
    <Aurora v-if="animate" :color-stops="colorStops" :amplitude="0.9" :blend="0.6" :speed="0.35" />
    <div v-else class="backdrop__still" />
    <div class="backdrop__veil" />
  </div>
</template>

<script setup lang="ts">
import { onUnmounted, ref } from 'vue';
import Aurora from '@/components/vendor/Aurora.vue';

/**
 * The landing's animated backdrop, and the only place in the product where decoration is
 * allowed to run behind content (docs/PRODOTTO.md records why, and where it stops).
 *
 * Two things this wrapper owns that the vendored component does not:
 *
 * 1. Reduced motion. A WebGL loop is exactly the kind of continuous animation
 *    prefers-reduced-motion exists to stop, and a CSS media query cannot reach inside a canvas.
 *    So the whole component is swapped for a static gradient rather than merely slowed: no
 *    render loop is started at all, which also spares the battery on the machines most likely
 *    to have the setting on.
 * 2. The palette, kept here so the vendored file stays verbatim and can be re-synced upstream.
 */

const colorStops = ['#0c100e', '#4e8a5a', '#0c100e'];

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
  background: radial-gradient(120% 80% at 50% 0%, rgba(78, 138, 90, 0.5), transparent 60%);
}

/*
 * The aurora peaks at the top of the viewport, which is where the headline sits. This veil is
 * what keeps the measured 16.75:1 honest at run time rather than only in the token table.
 */
.backdrop__veil {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(12, 16, 14, 0.72) 0%,
    rgba(12, 16, 14, 0.5) 45%,
    rgba(12, 16, 14, 0.92) 100%
  );
}
</style>
