# Mettere online RalSimulator

Su **Laravel Cloud**. Il vincolo dell'architettura — si deploya una cosa sola, Laravel serve
anche la SPA — resta intatto: un'applicazione, un dominio, nessun CORS.

Un hosting statico (GitHub Pages, Netlify, Vercel) qui non è un'alternativa più economica: è
incompatibile. Separando la SPA dall'API si perdono in un colpo l'origine unica, il cookie di
sessione Sanctum e i meta OpenGraph di `/s/{token}`. Sarebbe riscrivere l'autenticazione.

---

## Cosa serve

| | |
| --- | --- |
| PHP | 8.3+ (`composer.json` richiede `^8.3`) |
| Node | 22.12+ solo in fase di build |
| Database | MySQL o Postgres gestito — **non** SQLite, vedi sotto |

---

## 1. Creare l'applicazione

Cloud legge il repository e riconosce il monorepo: alla domanda su quale directory contenga
l'applicazione, scegli **`backend/`**. Diventa la root di build e di runtime, ma durante la
build tutte le directory del repository restano accessibili — ed è ciò che permette allo
script qui sotto di compilare la SPA che sta in `frontend/`.

Poi crea un **database** dal dashboard, nella stessa regione, e collegalo all'ambiente.

MySQL o Postgres è indifferente: nessuna migration usa SQL raw e l'unica colonna `json`
(`simulations.result`) viene sempre scritta e letta intera, mai interrogata. Cambia solo il
valore di `DB_CONNECTION` (`mysql` o `pgsql`).

---

## 2. Build commands

Da incollare nelle impostazioni dell'ambiente, **al posto** di quelli di default (che
includono `npm ci && npm run build`, qui da togliere: vedi «Cose da non fare»).

```bash
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# La SPA compilata non è versionata: backend/public/index.html, assets/ e icons/ stanno nel
# .gitignore perché sono artefatti. Vanno quindi ricostruiti a ogni deploy.
# pnpm, non npm: frontend/ ha un pnpm-lock.yaml e nessun package-lock.json.
# Si installa con npm perché corepack non è nel PATH dell'immagine di build di Cloud.
npm install -g pnpm@10

cd ../frontend
pnpm install --frozen-lockfile
pnpm build          # quasar build, poi scripts/copy-to-laravel.mjs copia in backend/public
cd ../backend

php artisan optimize
```

`php artisan optimize` funziona così com'è: la catch-all in `routes/web.php` è una closure, ma
Laravel la serializza (`laravel/serializable-closure`) e le route si cachano senza errori.

---

## 3. Deploy commands

Girano sull'infrastruttura viva, prima che il traffico passi alla nuova versione.

```bash
php artisan migrate --force
```

---

## 4. Primo avvio, una volta sola

Dalla console dell'ambiente:

```bash
php artisan db:seed --force          # le tabelle fiscali 2026

# Le opzioni servono: il runner dei comandi di Cloud non è interattivo, e senza di
# esse make:filament-user resterebbe fermo a chiedere nome, email e password.
php artisan make:filament-user --name="Nome Cognome" --email=tua@email --password='...'

php artisan user:promote tua@email   # senza questo /admin risponde 403
```

Il seeder **non** crea utenti fuori da `local`: `test@example.com` ha la password `password`
scritta in chiaro in `UserFactory`, e in produzione sarebbe un account vero con credenziali
pubbliche. È anche irrilevante tecnicamente — faker sta in `require-dev` e la build usa
`--no-dev`, quindi `User::factory()` lì non esiste proprio.

`db:seed --force` è **ri-eseguibile**: i due seeder fiscali usano `updateOrCreate` e non
duplicano niente. È il modo di pubblicare una correzione alle aliquote senza passare da
`/admin`.

---

## 5. Variabili d'ambiente

Il riferimento commentato è in **`backend/.env.production.example`**: contiene solo ciò che
cambia rispetto a `.env.example`, con accanto il perché di ogni valore.

In breve: `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` generata, `APP_URL` con https,
`DB_CONNECTION` (`mysql` o `pgsql`), `LOG_CHANNEL=stderr`, `SESSION_SECURE_COOKIE=true`.

Le credenziali del database (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`,
`DB_PASSWORD`) le inietta Cloud quando colleghi il database: non vanno scritte a mano.

---

## Cose da non fare

**Non lasciare `npm ci && npm run build` nella build.** `backend/package.json` esiste, ma il
suo unico consumatore è `resources/views/welcome.blade.php` — la pagina di default di Laravel,
che nessuna rotta raggiunge. Gli asset di Filament sono già compilati e versionati sotto
`backend/public/{css,js,fonts}/filament`, e `composer install` li ripubblica da sé
(`filament:upgrade` gira su `post-autoload-dump`).

**Non usare SQLite.** È un file su disco effimero: al primo redeploy sparirebbero tutte le
simulazioni salvate, e con loro ogni link già condiviso. Che è esattamente ciò che lo snapshot
in `simulations.result` esiste per garantire.

**Non aggiungere Redis.** Sessioni, cache e code stanno già tutte sul database.

**Non toccare la configurazione di Sanctum.** `config/sanctum.php` include
`currentRequestHost()`: il dominio che ha servito la richiesta è già stateful, sia quello
`.laravel.cloud` sia un dominio custom.

**Non configurare i trusted proxy.** Laravel riconosce Cloud da solo — `TrustProxies` chiama
`laravel_cloud()` e si fida di `*` — quindi https e IP del client arrivano già corretti.

---

## Health check

`/up` esiste già, registrato in `bootstrap/app.php`. Non c'è niente da aggiungere.
