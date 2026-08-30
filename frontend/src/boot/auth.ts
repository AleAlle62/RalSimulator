import { defineBoot } from '#q-app';
import { useAuthStore } from '@/stores/auth';

/**
 * Picks up an existing session cookie once, at start-up.
 *
 * Deliberately not awaited: signing in is optional everywhere in this product, so the first
 * paint must not wait on a request whose most common answer is "nobody". The header fills in
 * when it resolves; nothing downstream blocks on it.
 */
export default defineBoot(({ store }) => {
  const auth = useAuthStore(store);

  void auth.restore().catch(() => {
    // A failed restore leaves the visitor a guest, which is the app's resting state anyway.
  });
});
