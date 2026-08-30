<template>
  <main class="login">
    <SilkBackdrop />

    <div class="login__content">
      <AppHeader />

      <div class="login__panels">
        <section class="login__form glass" aria-labelledby="login-heading">
          <h1 id="login-heading" class="login__title">
            {{ registering ? 'Crea un account' : 'Accedi' }}
          </h1>
          <p class="login__lead">
            Serve solo a ritrovare le simulazioni salvate. Calcolare il netto non richiede un
            account e non lo richiederà mai.
          </p>

          <q-form class="login__fields" @submit.prevent="submit">
            <q-input
              v-if="registering"
              v-model="form.name"
              dark
              outlined
              label="Nome"
              autocomplete="name"
              :error="Boolean(errors.name)"
              :error-message="errors.name"
            />
            <q-input
              v-model="form.email"
              dark
              outlined
              type="email"
              label="Email"
              autocomplete="email"
              :error="Boolean(errors.email)"
              :error-message="errors.email"
            />
            <q-input
              v-model="form.password"
              dark
              outlined
              type="password"
              label="Password"
              :autocomplete="registering ? 'new-password' : 'current-password'"
              :error="Boolean(errors.password)"
              :error-message="errors.password"
            />
            <q-input
              v-if="registering"
              v-model="form.password_confirmation"
              dark
              outlined
              type="password"
              label="Ripeti la password"
              autocomplete="new-password"
            />

            <q-btn
              unelevated
              no-caps
              type="submit"
              color="primary"
              text-color="dark"
              class="login__submit"
              :label="registering ? 'Crea account' : 'Accedi'"
              :loading="busy"
            />
          </q-form>

          <p class="login__switch">
            {{ registering ? 'Hai già un account?' : 'Non hai un account?' }}
            <button type="button" class="login__toggle" @click="toggle">
              {{ registering ? 'Accedi' : 'Registrati' }}
            </button>
          </p>
        </section>

        <aside v-if="wideEnough" class="login__visual">
          <GlassEuro />
        </aside>
      </div>
    </div>
  </main>
</template>

<script setup lang="ts">
import { onUnmounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppHeader from '@/components/AppHeader.vue';
import GlassEuro from '@/components/GlassEuro.vue';
import SilkBackdrop from '@/components/SilkBackdrop.vue';
import { ApiError } from '@/services/api';
import { useAuthStore } from '@/stores/auth';

/**
 * Sign in on the left, a turning glass euro sign on the right.
 *
 * This is chrome, not a screen where figures are read, so it keeps the landing's treatment:
 * Silk behind, a glass panel over it. Field errors come from Laravel's validation bag rather
 * than being re-derived here, so the message next to an input is the same one the server
 * would have enforced anyway.
 */

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();

const registering = ref(false);
const busy = ref(false);

/**
 * Where to go once signed in.
 *
 * The result page sends people here to save a simulation, and dropping them on the wizard
 * afterwards would lose the very result they came to keep. Only same-site paths are honoured:
 * the parameter arrives from the URL, so anything else is an open redirect waiting to happen.
 */
function destination(): string {
  const wanted = route.query.ritorna;
  if (typeof wanted === 'string' && wanted.startsWith('/') && !wanted.startsWith('//')) {
    return wanted;
  }
  return '/simulazione';
}

/**
 * The 3D panel is mounted, not merely hidden, only above this width.
 *
 * Hiding it with `display: none` looked equivalent and was not: the canvas mounted at zero size,
 * its ResizeObserver measured nothing, and it stayed 300x150 when the panel later appeared. As a
 * bonus, a narrow screen — most likely a phone — now never creates a WebGL context at all.
 */
const wideQuery = window.matchMedia('(min-width: 60rem)');
const wideEnough = ref(wideQuery.matches);

const onWidthChange = (event: MediaQueryListEvent) => {
  wideEnough.value = event.matches;
};

wideQuery.addEventListener('change', onWidthChange);
onUnmounted(() => wideQuery.removeEventListener('change', onWidthChange));

const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const errors = reactive<Record<string, string | undefined>>({});

function toggle() {
  registering.value = !registering.value;
  clearErrors();
}

function clearErrors() {
  for (const key of Object.keys(errors)) delete errors[key];
}

async function submit() {
  busy.value = true;
  clearErrors();

  try {
    if (registering.value) {
      await auth.register({ ...form });
    } else {
      await auth.login({ email: form.email, password: form.password });
    }
    await router.push(destination());
  } catch (error) {
    if (error instanceof ApiError) {
      for (const [field, messages] of Object.entries(error.errors)) {
        errors[field] = messages[0];
      }
      // A failed login carries its message on `email`, since there is no field to blame.
      errors.email ??= error.status === 422 ? undefined : error.message;
    } else {
      errors.email = 'Qualcosa non ha funzionato. Riprova.';
    }
  } finally {
    busy.value = false;
  }
}
</script>

<style scoped lang="scss">
.login {
  position: relative;
  min-height: 100dvh;
  overflow: hidden;
  display: flex;
}

.login__content {
  position: relative;
  z-index: var(--z-content);
  width: min(72rem, 100% - 2.5rem);
  margin-inline: auto;
  padding-block: clamp(1rem, 3vh, 2rem);
  display: grid;
  grid-template-rows: auto 1fr;
  gap: clamp(1rem, 4vh, 2.5rem);
}

.login__panels {
  align-self: center;
  display: grid;
  gap: clamp(1.5rem, 4vw, 3rem);
  grid-template-columns: 1fr;
  align-items: center;

  @media (min-width: 60rem) {
    grid-template-columns: minmax(0, 26rem) minmax(0, 1fr);
  }
}

.login__form {
  border-radius: 16px;
  padding: clamp(1.25rem, 3vw, 2rem);
}

.login__title {
  margin: 0;
  font-size: clamp(1.5rem, 1.2rem + 1.4vw, 2rem);
  letter-spacing: -0.015em;
  font-weight: 700;
}

.login__lead {
  margin: 0.4rem 0 1.25rem;
  color: var(--muted);
  font-size: var(--step-small);
  text-wrap: pretty;
}

.login__fields {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.login__submit {
  margin-top: 0.35rem;
  font-weight: 700;
  padding: 0.65rem 1.5rem;
  border-radius: 10px;
}

.login__switch {
  margin: 1.1rem 0 0;
  font-size: var(--step-small);
  color: var(--muted);
}

.login__toggle {
  background: none;
  border: none;
  padding: 0;
  font: inherit;
  font-weight: 700;
  color: var(--azure);
  cursor: pointer;
  text-decoration: underline;
}

/* Presence is decided in script, by the same 60rem query: see wideEnough. */
.login__visual {
  height: min(28rem, 60vh);
}
</style>
