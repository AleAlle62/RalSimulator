# Mettere online

Una sola applicazione e un solo dominio: Laravel serve l'API **e** i file statici della SPA. Non
c'è un frontend da ospitare a parte, non c'è CORS da configurare, non c'è un secondo dominio.

**Il criterio per l'host è uno: non deve dormire.** I piani gratuiti che sospendono il container
dopo qualche minuto di inattività fanno aspettare venti secondi a chi apre il link — e questo
progetto viene aperto da qualcuno che ha meno di un minuto e non tornerà una seconda volta.

---

## Cosa serve prima, qualunque host

### 1. Il repository su GitHub

Tutti gli host qui sotto partono da lì.

```bash
cd ~/Desktop/RalSimulator && git status
```

Se il push fallisce per il token scaduto: `gh auth login -h github.com`.

### 2. La build della SPA dentro `backend/public`

```bash
pnpm --dir ~/Desktop/RalSimulator/frontend run build
```

`pnpm build` compila in `dist/spa` e poi lancia `scripts/copy-to-laravel.mjs`, che copia dentro
`backend/public` rimuovendo **solo** ciò che appartiene alla SPA (`assets/`, `icons/`,
`index.html`). Non si può puntare `distDir` direttamente su `backend/public`: Quasar fa un
`removeSync()` dell'intera cartella prima di ogni build e cancellerebbe `index.php` e tutti gli
asset di Filament.

**Decisione da prendere:** committare `backend/public/assets` oppure buildare in CI. Committarli è
la strada corta e per un take-home va benissimo; se li committi, ricordati che ogni build sporca
il diff.

### 3. Le variabili d'ambiente di produzione

`.env` non è nel repository. In produzione servono almeno:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://il-tuo-dominio
APP_KEY=            # php artisan key:generate --show
APP_LOCALE=it
APP_TIMEZONE=Europe/Rome

DB_CONNECTION=pgsql # o mysql, secondo l'host
SESSION_DRIVER=database
```

**`APP_DEBUG=false` non è un dettaglio**: con `true`, la pagina di errore di Laravel mostra le
variabili d'ambiente, credenziali del database comprese.

`SANCTUM_STATEFUL_DOMAINS` **non va impostata**: `config/sanctum.php` usa già
`Sanctum::currentRequestHost()`, e l'architettura è same-origin per definizione.

---

## Le due strade

### Laravel Cloud — la più breve

Fatta da chi fa Laravel: colleghi il repo GitHub, riconosce il framework, ti dà Postgres gestito e
HTTPS. Ha un piano che non sospende.

1. Collega il repository, seleziona `backend/` come root dell'applicazione.
2. Metti le variabili qui sopra nel pannello.
3. Comandi di deploy: `composer install --no-dev --optimize-autoloader` e `php artisan migrate --force`.
4. Poi i seeder, **una volta sola** (vedi sotto).

Costa. Se il progetto deve restare gratuito, vai su Oracle.

### Oracle Cloud Always Free — gratis davvero, ma la monti tu

Una VM ARM Ampere che non scade e non dorme. In cambio installi tu nginx, PHP 8.4, Postgres e
Certbot. Conta un paio d'ore la prima volta.

Nota per la macchina locale, se passi a Postgres:

```bash
sudo dnf install php-pgsql
```

---

## Dopo il primo deploy, in ordine

### 1. Migrazioni e dati fiscali

```bash
php artisan migrate --force
php artisan db:seed --class=TaxYear2026Seeder
php artisan db:seed --class=TaxPlaces2026Seeder
```

I due seeder vanno lanciati **una volta sola**. Senza, il repository fiscale si rifiuta di
rispondere e ogni simulazione fallisce — che è il comportamento voluto: meglio un errore che un
numero plausibile e sbagliato.

### 2. Il tuo utente amministratore

```bash
php artisan make:filament-user
php artisan user:promote tua@email
```

Il secondo comando è obbligatorio: `is_admin` è fuori dai `fillable`, quindi un utente appena
creato **non** entra in `/admin` finché non lo promuovi. È voluto — senza, chiunque si registri
dalla SPA entrerebbe nel pannello.

### 3. Le cache

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Da rilanciare a ogni deploy. Se cambi `.env` senza rifare `config:cache`, Laravel continua a
leggere i valori vecchi — è il modo più comune di perdere mezz'ora.

### 4. Verifica che le tre rotte facciano tre cose diverse

- `/` → la SPA
- `/api/tax-years/2026` → JSON con le costanti
- `/admin` → il login di Filament

Se dopo una build il server risponde 500 a tutto: **riavvialo**. Il processo tiene aperto il
vecchio inode della document root, che la copia degli asset ha appena sostituito.

---

## Il pannello Filament, in breve

Ci si arriva da **`/admin`** — in sviluppo `http://localhost:9200/admin` (il dev server fa da
proxy) oppure `http://localhost:5174/admin` diretto.

Serve un utente con `is_admin = true`: `User::canAccessPanel()` lo pretende. In locale, se non ce
l'hai ancora:

```bash
php artisan user:promote basketallegrini@gmail.com
```

Dentro c'è **una sola risorsa**, «Anno fiscale», con le altre quattro tabelle come schede annidate
nella pagina di modifica — costanti, regioni, comuni, scaglioni. Non sono cinque voci di menu
separate perché tutte dipendono da un anno, e annidarle lo rende esplicito invece di lasciarlo a
un filtro.

Due cose che il form non ti lascia fare, di proposito: la chiave di una costante è un select sui
casi di `TaxConstantKey` e non testo libero, e non si modifica dopo la creazione — cambiarla
equivarrebbe a spostare un valore su un'altra costante.

La creazione invece resta aperta ovunque: `CLAUDE.md` dice che aggiornare il 2027 dev'essere «un
inserimento di dati, non un deploy», e bloccarla contraddirebbe il motivo per cui Filament esiste
in questo progetto.
