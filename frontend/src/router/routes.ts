import type { RouteRecordRaw } from 'vue-router';

/**
 * No shared layout wrapper: the landing and the calculator are deliberately different worlds
 * (docs/PRODOTTO.md — decoration stops at the CTA), so neither inherits the other's chrome.
 */
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/pages/LandingPage.vue'),
    meta: { title: "Da RAL a stipendio netto — anno d'imposta 2026" },
  },

  // Always leave this as last one.
  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/ErrorNotFound.vue'),
  },
];

export default routes;
