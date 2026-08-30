<template>
  <header class="header glass">
    <router-link to="/" class="header__brand" aria-label="RalSimulator, torna alla pagina iniziale">
      <span class="header__mark" aria-hidden="true" />
      <span class="header__name">RalSimulator</span>
    </router-link>

    <nav class="header__nav" aria-label="Navigazione principale">
      <q-btn
        v-if="auth.isAuthenticated"
        flat
        no-caps
        dense
        class="header__link"
        label="Le mie simulazioni"
        to="/simulazioni"
      />

      <template v-if="auth.isAuthenticated">
        <span class="header__who">{{ auth.user?.name }}</span>
        <q-btn
          flat
          no-caps
          dense
          class="header__link"
          label="Esci"
          :loading="leaving"
          @click="signOut"
        />
      </template>

      <q-btn
        v-else
        outline
        no-caps
        dense
        color="primary"
        class="header__signin"
        label="Accedi"
        to="/accedi"
      />
    </nav>
  </header>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

/**
 * Shared by the landing and the calculator, which are otherwise different worlds. It is the one
 * piece of chrome that spans both, so it is also the only glass element on the screens where
 * figures are read: it sits above the content, never behind a number.
 *
 * Signing in is optional throughout the product, so this never blocks: a guest simply sees
 * "Accedi" and everything else keeps working.
 */

const auth = useAuthStore();
const router = useRouter();
const leaving = ref(false);

async function signOut() {
  leaving.value = true;
  try {
    await auth.logout();
    await router.push('/');
  } finally {
    leaving.value = false;
  }
}
</script>

<style scoped lang="scss">
.header {
  position: relative;
  z-index: var(--z-header);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.6rem 1rem;
  border-radius: 12px;
}

.header__brand {
  display: inline-flex;
  align-items: center;
  gap: 0.6rem;
  color: var(--bone);
  text-decoration: none;
  font-weight: 700;
}

/* A filled square with a notch: the gross, and the part of it that leaves. */
.header__mark {
  width: 1.1rem;
  height: 1.1rem;
  border-radius: 4px;
  background: var(--azure);
  clip-path: polygon(0 0, 100% 0, 100% 62%, 62% 62%, 62% 100%, 0 100%);
}

.header__name {
  font-size: var(--step-small);
  letter-spacing: -0.01em;
}

.header__nav {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.header__link {
  color: var(--bone);
  font-size: var(--step-small);
}

.header__who {
  font-size: var(--step-small);
  color: var(--muted);
  padding-inline: 0.5rem;
}

.header__signin {
  font-size: var(--step-small);
  font-weight: 700;
}

@media (max-width: 560px) {
  .header__who {
    display: none;
  }
}
</style>
