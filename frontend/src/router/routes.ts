import type { RouteRecordRaw } from 'vue-router';

/**
 * No shared layout wrapper: the landing and the calculator are deliberately different worlds
 * (see the README), so neither inherits the other's chrome.
 * AppHeader is what they do share, and each page places it itself.
 */
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/pages/LandingPage.vue'),
  },
  {
    path: '/simulazione',
    component: () => import('@/pages/WizardPage.vue'),
  },
  {
    path: '/risultato/:token',
    component: () => import('@/pages/ResultPage.vue'),
  },
  {
    path: '/accedi',
    component: () => import('@/pages/LoginPage.vue'),
  },
  {
    path: '/simulazioni',
    component: () => import('@/pages/SavedSimulationsPage.vue'),
  },

  // Always leave this as last one.
  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/ErrorNotFound.vue'),
  },
];

export default routes;
