/**
 * The one place that talks to Laravel.
 *
 * Session-cookie auth, not bearer tokens: the SPA is served from the same origin as the API, so
 * the cookie Sanctum issues is the whole mechanism. Three consequences are encoded here rather
 * than repeated at every call site:
 *
 * 1. `Accept: application/json` on every request. Without it Laravel answers a guest on a
 *    protected route by trying to redirect to a `login` route this app does not define, and the
 *    401 arrives as a 500. The backend was hardened against that too, but the header is the
 *    real fix and it belongs here.
 * 2. `credentials: 'same-origin'`, or the session cookie never leaves the browser.
 * 3. The CSRF dance. Laravel rotates the token when a session is regenerated, so the cookie is
 *    re-read before every write rather than cached.
 */

export class ApiError extends Error {
  constructor(
    readonly status: number,
    message: string,
    /** Laravel's field-keyed validation bag, present on 422 and nowhere else. */
    readonly errors: Record<string, string[]> = {},
  ) {
    super(message);
    this.name = 'ApiError';
  }

  /** First message for a field, which is all a form ever shows next to an input. */
  fieldError(field: string): string | undefined {
    return this.errors[field]?.[0];
  }
}

const readCookie = (name: string): string | null => {
  const match = document.cookie.match(new RegExp(`(^|;\\s*)${name}=([^;]*)`));
  return match?.[2] ? decodeURIComponent(match[2]) : null;
};

let csrfReady = false;

async function ensureCsrfCookie(): Promise<void> {
  if (csrfReady && readCookie('XSRF-TOKEN')) return;

  await fetch('/sanctum/csrf-cookie', {
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
  });

  csrfReady = true;
}

async function request<T>(method: string, path: string, body?: unknown): Promise<T> {
  const isWrite = method !== 'GET';

  if (isWrite) {
    await ensureCsrfCookie();
  }

  const headers: Record<string, string> = { Accept: 'application/json' };

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json';
  }

  if (isWrite) {
    // Re-read rather than reuse: session regeneration on login rotates this value, and a stale
    // token is indistinguishable from a forged one to the server (419).
    const token = readCookie('XSRF-TOKEN');
    if (token) headers['X-XSRF-TOKEN'] = token;
  }

  const init: RequestInit = { method, credentials: 'same-origin', headers };

  // Assigned rather than always present: under exactOptionalPropertyTypes an explicit
  // `body: undefined` is not the same as omitting the key, and GET must omit it.
  if (body !== undefined) {
    init.body = JSON.stringify(body);
  }

  const response = await fetch(path, init);

  if (response.status === 204) {
    return undefined as T;
  }

  const payload = (await response.json().catch(() => null)) as {
    message?: string;
    errors?: Record<string, string[]>;
  } | null;

  if (!response.ok) {
    throw new ApiError(
      response.status,
      payload?.message ?? 'Richiesta non riuscita.',
      payload?.errors ?? {},
    );
  }

  return payload as T;
}

export const api = {
  get: <T>(path: string) => request<T>('GET', path),
  post: <T>(path: string, body?: unknown) => request<T>('POST', path, body),
  delete: <T>(path: string) => request<T>('DELETE', path),
};
