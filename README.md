# RalSimulator

Da **RAL lorda** a **stipendio netto**, anno d'imposta 2026: contributi, IRPEF, addizionali e
somme esenti voce per voce, con le buste calcolate davvero. Le simulazioni si salvano e si
condividono con un link.

Take-home per un colloquio in Jet HR ([istruction.md](istruction.md)).

**Caso di riferimento:** RAL 35.000 €, commercio, 14 mensilità, Milano → **25.967,22 €** netti,
busta ordinaria **1.910,42 €**, tredicesima **1.521,07 €**.

---

## Architettura in una riga

Si deploya **una cosa sola**: Quasar compila in file statici, Laravel li serve dalla stessa
origine dell'API. Niente CORS, niente secondo dominio, cookie di sessione che funziona e basta.

```
frontend/   SPA Quasar  ──build──►  backend/public/
backend/    Laravel — API, motore fiscale, auth, Filament
```

| Rotta | Serve |
| --- | --- |
| `/` e tutto il resto | la SPA |
| `/api/*` | API JSON |
| `/admin` | pannello Filament |

---

## Avviare

Servono **due processi**. Il frontend gira sulla 9200 e fa da proxy verso Laravel sulla 5174,
così anche in sviluppo è same-origin come in produzione.

### Backend (e Filament)

```bash
cd backend && composer install && php artisan migrate --seed && php artisan serve --port=5174
```

Filament sta su **`/admin`** → `http://localhost:9200/admin` (o `:5174` diretto).

Serve un utente con `is_admin = true`. `is_admin` è **fuori dai fillable**, quindi chi si
registra dalla SPA non entra nel pannello: va promosso a mano.

```bash
php artisan make:filament-user          # se non hai ancora un utente
php artisan user:promote tua@email      # obbligatorio, senza questo /admin dà 403
```

### Frontend

```bash
cd frontend && pnpm install && pnpm dev        # http://localhost:9200
```

Per compilare dentro Laravel:

```bash
pnpm build      # compila in dist/spa, poi copia in backend/public
```

> `distDir` **non** può puntare a `backend/public`: Quasar fa un `removeSync()` dell'intera
> cartella prima di ogni build e cancellerebbe `index.php` e gli asset di Filament. Da qui lo
> script `scripts/copy-to-laravel.mjs`, che rimuove solo ciò che appartiene alla SPA.

### Test

```bash
cd backend && ./vendor/bin/pest      # 121 test
```

---

## Il database

Sei tabelle. L'idea che le governa: **le aliquote sono dati, non codice.**

| Tabella | Contiene |
| --- | --- |
| `tax_years` | anno, etichetta, `published_at` |
| `tax_regions` | regione + fonte |
| `tax_municipalities` | comune, aliquota, soglia esenzione, **delibera e data** |
| `tax_brackets` | tutte le liste di scaglioni: `kind` + `owner_id` + `position` |
| `tax_constants` | le 20 costanti chiave/valore |
| `simulations` | token, utente (nullable), input, `result` JSON |

Tre conseguenze volute:

- **Aggiornare il 2027 è un inserimento di dati, non un deploy.**
- Ogni costante porta `source_url` e `source_label`: la fonte sta accanto al numero, non nel
  README.
- `simulations.result` è uno **snapshot**, non una cache. Non si ricalcola mai alla lettura: una
  simulazione fatta oggi e riaperta l'anno prossimo mostra i numeri con cui è stata fatta,
  altrimenti un link condiviso cambierebbe contenuto sotto i piedi di chi l'ha ricevuto.

Il repository si **rifiuta di rispondere** se manca un dato (anno non pubblicato, comune
sconosciuto, regione senza scaglioni). Ognuno di questi poteva essere uno zero di default — ed è
esattamente il fallimento da evitare, perché il risultato sembrerebbe uno stipendio, plausibile
al centesimo e sbagliato.

Seedate 8 regioni e 8 città (Milano, Roma, Napoli, Bologna, Firenze, Bari, Venezia, Palermo),
prese dall'endpoint MEF.

---

## Backend: come sono fatte le chiamate

`routes/api.php` → FormRequest (valida) → Controller (orchestra) → `app/Domain/` (calcola) →
Resource (serializza).

| Metodo | Rotta | Auth | Cosa fa |
| --- | --- | --- | --- |
| `POST` | `/api/simulations` | no | calcola, salva, torna token + risultato |
| `GET` | `/api/simulations/{token}` | no | legge lo snapshot |
| `GET` | `/api/tax-years/{year}` | no | costanti e scaglioni, con fonte |
| `GET` | `/api/tax-years/{year}/municipalities` | no | solo i comuni che calcolano davvero |
| `GET` | `/api/me/simulations` | sì | le tue simulazioni |
| `POST` | `/api/me/simulations/{token}/claim` | sì | adotta una simulazione senza proprietario |
| `DELETE` | `/api/me/simulations/{id}` | sì | elimina la tua |
| `POST` | `/api/register` · `/api/login` · `/api/logout` · `GET /api/me` | — | Sanctum |

Tre regole:

- **L'anno non è mai un input.** Lo risolve `TaxYearRepository::currentYear()`, così una
  richiesta non può chiedere un anno più vecchio e più favorevole.
- **Il motore è puro:** input e configurazione dentro, risultato fuori. Nessuna query, nessuna
  data di sistema, nessuno stato. `app/Domain/` non dipende da Laravel — i test lo costruiscono
  a mano, senza container e senza database.
- **La validazione sta nelle FormRequest**, mai nel motore.

Chi non è tuo risponde **404 e non 403**: chi chiama possiede solo un token, e rispondere «è di
un altro» trasformerebbe un token indovinato in un modo per sondare quali sono presi.

---

## Le librerie

**Backend** — Laravel 13 · Sanctum 4 (auth) · Filament 5 (admin) · Pest 4 (test) · SQLite in
sviluppo.

**Frontend** — Quasar 2 / Vue 3 · TypeScript · Pinia · `ogl` (sfondo animato Silk, WebGL) ·
`three` (l'euro 3D del login, ~600 KB, costo accettato consapevolmente).

Nessun client HTTP: `fetch` nativo in `services/api.ts`. Nessuna libreria di grafici: la barra
delle proporzioni sono quattro `<span>` con una larghezza in percentuale.

`Silk.vue` è **vendorizzato a mano** da [Vue Bits](https://vue-bits.dev) (MIT): distribuiscono i
sorgenti con `jsrepo`, non come pacchetto runtime. Resta verbatim per poterlo risincronizzare;
palette e `prefers-reduced-motion` stanno nel wrapper accanto.

---

## Login e sessione

**Cookie di sessione, non token in `localStorage`.** Same-origin significa che il cookie
funziona e basta, e un token in `localStorage` sarebbe leggibile da qualsiasi XSS.

**Backend** — `bootstrap/app.php` chiama `$middleware->statefulApi()`. Sanctum applica
`StartSession` + verifica CSRF **solo** se `Referer`/`Origin` combaciano con
`config('sanctum.stateful')`, dove c'è `Sanctum::currentRequestHost()`: qualunque host serva la
richiesta è per definizione quello giusto, coerente con l'architettura a un'origine sola.
`register` e `login` fanno `session()->regenerate()` (fissazione di sessione).

**Frontend** — `services/api.ts`, unico posto che parla con Laravel:

1. `Accept: application/json` **sempre**. Senza, un ospite su rotta protetta fa crashare Laravel
   con 500 invece di un 401 pulito: prova a redirigere a una route `login` che qui non esiste.
2. `credentials: 'same-origin'`, o il cookie non parte.
3. Prima di ogni scrittura: `GET /sanctum/csrf-cookie`, poi rilegge `XSRF-TOKEN` dai cookie e lo
   rimanda come header `X-XSRF-TOKEN`. **Rilegge**, non riusa: il login rigenera la sessione e
   ruota il token, e uno stale è indistinguibile da uno forgiato (419).

> ⚠️ Nel proxy di `quasar.config.ts`, `changeOrigin` **deve restare `false`**. A `true` riscrive
> l'header `Host` a `localhost:5174` mentre il `Referer` del browser resta 9200: Sanctum
> confronta esattamente quei due, non combaciano mai, e non applica alcun middleware **in
> silenzio** — né sessione né CSRF. Login e registrazione muoiono su «Session store not set on
> request», e `POST /api/simulations` sembra funzionare solo perché salta anche il CSRF.

**Salvataggio.** Ogni simulazione è salvata sempre, anche da ospite (`user_id = null`,
raggiungibile solo dal token). Chi si registra dopo la adotta con `claim`, altrimenti resterebbe
orfana con il link solo nella barra degli indirizzi.

---

## Frontend: come funziona

```
src/
├── pages/        Landing · Wizard · Result · Login · SavedSimulations
├── components/   AppHeader · SilkBackdrop · GlassEuro · BreakdownRow · vendor/Silk
├── composables/  useCurrency · useCountUp
├── services/     api.ts — l'unico posto che chiama Laravel
└── stores/       auth · simulation (Pinia)
```

**Il client non calcola niente.** Il wizard raccoglie quattro risposte (RAL → mensilità →
settore → luogo), le manda, e mostra lo snapshot che torna. Se un numero deve comparire a
schermo e l'API non lo restituisce, si cambia l'API — non si calcola qui. Fonte unica di verità.

Il percorso: `/` landing → `/simulazione` wizard → `/risultato/{token}` → `/simulazioni` le tue.

Scelte di interfaccia che hanno un perché:

- **Il colore non porta mai da solo il significato.** Ogni riga del dettaglio ha un operatore
  `−` `+` `=` che gli screen reader leggono in parole: rosso/verde è la forma più comune di
  daltonismo.
- **Le somme esenti stanno fuori dalla barra.** Non sono una fetta della RAL, si aggiungono al
  netto: metterle dentro farebbe sommare le proporzioni a più del 100 %.
- **Tema:** sfondo WebGL animato e card in vetro ovunque, ma i pannelli che portano cifre usano
  un vetro al 93 % di opacità. Contrasti misurati sul caso peggiore reale (pixel più chiaro del
  canvas, velo al punto più trasparente, nessuno sconto per la sfocatura): corpo 16,57:1,
  secondario 7,67:1. `prefers-reduced-motion` non avvia proprio il render loop.
- Font **Atkinson Hyperlegible Next**, disegnato dal Braille Institute per la leggibilità a
  bassa visione.

---

## Cambiare le aliquote da Filament

`/admin` → **Anno fiscale** → apri il 2026. Le altre quattro tabelle sono schede annidate nella
pagina di modifica, non voci di menu separate: dipendono tutte da un anno, e annidarle lo rende
esplicito invece di lasciarlo a un filtro.

| Scheda | Cosa cambi |
| --- | --- |
| **Costanti** | le 20 costanti — aliquote contributive, soglie, importi delle detrazioni |
| **Regioni** | le regioni e la fonte |
| **Comuni** | aliquota comunale, soglia di esenzione, delibera |
| **Scaglioni** | IRPEF, cuneo, addizionale regionale e comunale |

Due cose che il form non lascia fare, di proposito:

- **La chiave di una costante non è testo libero:** è un select sui casi di `TaxConstantKey`.
  Una chiave che il motore non conosce non può nemmeno essere digitata.
- **La chiave non si modifica dopo la creazione:** cambiarla equivarrebbe a spostare un valore
  su un'altra costante.

La creazione resta aperta ovunque: aggiornare il 2027 dev'essere un inserimento di dati, e
bloccarla contraddirebbe il motivo per cui Filament esiste qui.

Per un anno nuovo: crea `tax_years`, popola costanti e scaglioni, poi valorizza `published_at` —
finché è nullo l'anno non viene servito.

---

## I calcoli

Ogni cifra è un parametro di legge 2026 verificato su fonte primaria.

### L'ordine delle operazioni

Non è arbitrario: è quello in cui le regole si applicano in busta. I contributi vengono per primi
perché **riducono la base imponibile** di tutto il resto.

```
1. contributi   = min(RAL, 122.295) × aliquota_settore
                  + max(0, base − 56.224) × 1%
2. imponibile   = RAL − contributi
3. IRPEF lorda  = scaglioni progressivi sull'imponibile
4. detrazioni   = detrazione lavoro + ulteriore detrazione cuneo (se imponibile > 20.000)
5. IRPEF netta  = max(0, lorda − detrazioni)          ← mai negativa
6. addizionali  = regionale + comunale, entrambe sull'imponibile
7. somme esenti = bonus cuneo (< 20.000) + trattamento integrativo

   trattenute = contributi + IRPEF netta + addizionali
   NETTO      = RAL − trattenute + somme esenti
```

### Le costanti

**Contributi INPS** (INPS circ. 6/2026) — commercio **9,19 %**, industria **9,49 %** (lo 0,30 %
di differenza è la CIGS). Aliquota aggiuntiva 1 % oltre **56.224 €**, massimale **122.295 €**.

> Molti calcolatori online riportano ancora 52.190 € per la soglia aggiuntiva: è uno dei punti
> in cui i risultati divergono.

**IRPEF** (L. 199/2025, Legge di Bilancio 2026) — 23 % fino a 28.000 · **33 %** da 28.000 a
50.000 · 43 % oltre. La seconda aliquota è scesa dal 35 % al 33 % dal 01/01/2026.

**Detrazione lavoro dipendente** (art. 13 TUIR) — 1.955 € fissi fino a 15.000; poi due raccordi
lineari distinti, non uno solo: `1.910 + 1.190 × (28.000 − imp) / 13.000` fino a 28.000, e
`1.910 × (50.000 − imp) / 22.000` fino a 50.000; zero oltre.

**Taglio del cuneo** — sotto un nome solo convivono **due strumenti mutuamente esclusivi**, e la
differenza non è formale:

- **sotto 20.000 di imponibile** → somma **esente**, erogata in busta e non tassata. Non tocca
  l'IRPEF. 7,1 % fino a 8.500 · 5,3 % fino a 15.000 · 4,8 % fino a 20.000, applicata
  all'**intero** imponibile.
- **sopra 20.000** → **detrazione** ordinaria che abbatte l'IRPEF lorda. 1.000 € fino a 32.000,
  poi in calo lineare fino a zero a 40.000.

**Trattamento integrativo** (ex bonus Renzi, art. 1 D.L. 3/2020 mod. L. 207/2024) — 1.200 € fino
a 15.000 di imponibile **se** l'IRPEF lorda supera la detrazione lavoro **meno 75 €**.

> **L'offset di 75 € è il dettaglio che sbagliano quasi tutti.** Compensa l'aumento della
> detrazione da 1.880 a 1.955: senza, l'innalzamento avrebbe spinto fuori dal beneficio proprio
> i redditi che doveva proteggere. La soglia effettiva del test resta **1.880 €**. Decide l'esito
> per imponibili fra ~8.200 e ~8.500: lì il calcolo corretto concede 1.200 € e quello ingenuo li
> nega.

**Addizionali** — Lombardia progressiva a scaglioni (1,23 → 1,73 %). Milano 0,8 % con
**esenzione** fino a 23.000 € di imponibile: è un'esenzione, **non una franchigia**. Un euro
sopra la soglia, lo 0,8 % colpisce l'intero imponibile.

### Le somme esenti non sono una fetta della RAL

Bonus cuneo e trattamento integrativo **non escono dal lordo: ci si aggiungono**. Il netto può
quindi superare quello che si otterrebbe sottraendo alla RAL tutte le trattenute. Ogni grafico
che divide la RAL deve tenerle **fuori** dalle fette, altrimenti le proporzioni sommano più del
100 %.

### Le mensilità aggiuntive rendono meno

A parità di lordo, 13ª e 14ª lasciano in mano **meno** di una busta ordinaria. Due ragioni
entrambe reali: le detrazioni sono già consumate dalle dodici ordinarie (quindi sulle extra si
trattiene all'aliquota marginale piena), e le addizionali si trattengono sulle ordinarie.

**I totali annui sono la verità, le buste si derivano da quelli** — mai il contrario, così
risommano sempre al netto annuo esatto.

### I tre gradini

In tre punti il netto **scende** al crescere del lordo. Non sono bug: sono soglie di legge dove
un beneficio si ricalcola sull'intero reddito o si perde.

| Imponibile | Cosa succede |
| --- | --- |
| **8.500 €** | la somma esente passa dal 7,1 % al 5,3 % dell'intero imponibile |
| **15.000 €** | si perde il trattamento integrativo (1.200 €), l'esente scende al 4,8 % |
| **23.000 €** | finisce l'esenzione comunale di Milano: lo 0,8 % colpisce tutto |

Il test più importante della suite percorre le RAL da 1.000 a 300.000 € a passi di 100 e pretende
che **ogni** calo del netto cada su una di queste tre soglie. Un calo altrove è un errore di
implementazione.

### Verifica

RAL 35.000, commercio, Milano:

| Passo | Calcolo | Risultato |
| --- | --- | --- |
| Contributi | 35.000 × 9,19 % | 3.216,50 |
| Imponibile | 35.000 − 3.216,50 | 31.783,50 |
| IRPEF lorda | 28.000 × 23 % + 3.783,50 × 33 % | 7.688,56 |
| Detrazione lavoro | 1.910 × (50.000 − 31.783,50) / 22.000 | 1.581,52 |
| Ulteriore detrazione | forfait, sotto i 32.000 | 1.000,00 |
| IRPEF netta | 7.688,56 − 1.581,52 − 1.000 | 5.107,03 |
| Add. regionale | 15.000×1,23 % + 13.000×1,58 % + 3.783,50×1,72 % | 454,98 |
| Add. comunale | 31.783,50 × 0,8 % | 254,27 |
| **Netto annuo** | | **25.967,22** |

Verificato con un'implementazione indipendente su 29 RAL diverse: coincidono al centesimo.

---

## Fuori scope

Costo azienda · TFR · familiari a carico e oneri detraibili · export PDF · redditi diversi da
lavoro dipendente · anni diversi dal 2026 (lo schema è pronto, i dati no) · comuni con scaglioni
comunali propri, come Torino e Genova.

**Non sostituisce il conteggio di un consulente del lavoro.**
