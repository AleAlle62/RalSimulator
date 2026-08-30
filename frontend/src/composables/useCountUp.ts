import { onUnmounted, ref } from 'vue';

interface CountUpOptions {
  durationMs?: number;
  locale?: string;
}

/**
 * Counts a figure up to its final value once, on mount.
 *
 * It exists for one moment on the landing: the net salary resolving out of the gross. Used
 * anywhere a reader has to *verify* a number it would be a liability, so it stays a composable
 * the landing opts into rather than a formatter every screen inherits.
 *
 * **The resting value is the real one, always.** The obvious implementation seeds the ref at
 * zero and animates upward, which leaves "0,00 €" on screen whenever requestAnimationFrame
 * never runs: a hidden tab, a prerender, a headless screenshot. On this product that would not
 * degrade to a missing animation, it would state that the net salary is zero. So the ref starts
 * at the target and the countdown to zero happens inside the first frame — if that frame never
 * arrives, the correct figure was on screen the whole time.
 */
export function useCountUp(target: number, options: CountUpOptions = {}) {
  const { durationMs = 1000, locale = 'it-IT' } = options;

  const format = (value: number) =>
    value.toLocaleString(locale, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  const displayed = ref(format(target));
  let frame = 0;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const isVisible = document.visibilityState === 'visible';

  if (!prefersReducedMotion && isVisible) {
    // Quintic ease-out: fast first, settling at the end, so the final digits are readable
    // rather than still blurring past.
    const ease = (t: number) => 1 - Math.pow(1 - t, 5);
    let start = 0;

    const tick = (now: number) => {
      if (start === 0) {
        start = now;
      }

      const progress = Math.min((now - start) / durationMs, 1);
      displayed.value = format(target * ease(progress));

      if (progress < 1) {
        frame = requestAnimationFrame(tick);
      }
    };

    frame = requestAnimationFrame(tick);
  }

  onUnmounted(() => cancelAnimationFrame(frame));

  return { displayed };
}
