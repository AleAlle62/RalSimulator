# Stato e piano

Aggiornato al **29 agosto 2026**. Questo file dice a che punto siamo e da dove si riparte:
va letto per primo aprendo una sessione nuova.

Le decisioni architetturali stanno in [../CLAUDE.md](../CLAUDE.md), le regole fiscali in
[FISCO-2026.md](FISCO-2026.md), le scelte di interfaccia in [PRODOTTO.md](PRODOTTO.md).

---

## Come si lavora su questo progetto

**Un file alla volta.** Prima si dichiara nome, posizione, cosa fa e perché — in poche righe —
poi si aspetta l'ok, poi si scrive. Vale anche per i file piccoli e ovvi. "Procedi" significa
"proponi il prossimo file", non "scrivi tutto il blocco".

Domande brevi, una alla volta, in linguaggio semplice sui temi fiscali.

---

## Fatto

### Backend, impianto

- Laravel 13.29 su PHP 8.4, SQLite in sviluppo
- Sanctum 4 installato, `routes/api.php` creato
- Filament 5.7, pannello su `/admin`, `AdminPanelProvider` registrato
- Pest 4.7

### Accesso al pannello

- Colonna `users.is_admin`, default `false`, fuori dai fillable
- `User::canAccessPanel()` — senza, chiunque si registri entrerebbe in `/admin`
- Comando `php artisan user:promote {email}` — serve anche in produzione dopo il deploy
- 5 test in `tests/Feature/AdminAccessTest.php`

Per entrare: `php artisan make:filament-user` poi `php artisan user:promote TUA@EMAIL`.

### Motore fiscale — completo, 75 test verdi

26 classi in `app/Domain/`, **nessuna dipendenza da Laravel**: i test costruiscono tutto a
mano, senza container e senza database.

```
app/Domain/
├── SalaryCalculator     l'orchestratore
├── SalaryInput          RAL, mensilità, settore
├── SalaryBreakdown      il risultato completo
├── Sector               commercio | industria
├── Tax/
│   ├── Brackets/        Bracket · Brackets (apply, rateFor)
│   ├── Contributions/   config · calcolatore · risultato
│   ├── Irpef/           IrpefCalculator (lorda, aliquota marginale)
│   ├── Reliefs/         3 config + ReliefsConfig · calcolatore · risultato
│   ├── Surtaxes/        config · calcolatore · risultato
│   ├── TaxYearConfig    aggrega le config fiscali
│   └── TaxYear2026      i valori con le fonti
└── Payslips/            Kind · Payslip · Schedule · Input · Calculator
```

**Verificato:** RAL 35.000 commercio 14 mensilità → netto **25.967,22 €**, busta ordinaria
**1.910,42 €**, tredicesima **1.521,07 €**. Coincidono al centesimo con l'implementazione
TypeScript precedente, a sua volta verificata su 29 RAL.

Il test più importante percorre i redditi da 1.000 a 300.000 € a passi di 100 e pretende che
**ogni** calo del netto cada su una delle tre soglie note (8.500 · 15.000 · 23.000).

### Database — schema creato e popolato

Sei tabelle migrate:

| Tabella | Contiene |
| --- | --- |
| `tax_years` | anno, etichetta, `published_at`, note |
| `tax_regions` | nome regione + fonte (gli scaglioni stanno in `tax_brackets`) |
| `tax_municipalities` | nome, provincia, codice catastale, aliquota, soglia esenzione, **delibera** e data |
| `tax_brackets` | tutte le liste di scaglioni: `kind` + `owner_id` + `position` |
| `tax_constants` | le 20 costanti, chiave/valore, con fonte |
| `simulations` | token, utente (null), input, `result` JSON come **snapshot** |

`source_url` e `source_label` su brackets, constants, regions e municipalities.

Le costanti sono **20 e non 22**: aliquota comunale e soglia di esenzione cambiano da comune a
comune, quindi sono colonne di `tax_municipalities`, non costanti dell'anno.

### Seeder e repository — completo, 9 test verdi

```
app/TaxTables/
├── TaxConstantKey            le 20 chiavi ammesse
├── BracketKind               irpef · wedge_cut_exempt_bonus · regional_surtax · municipal_surtax
├── TaxYearRepository         righe → TaxYearConfig
└── MissingTaxDataException   quando le tabelle non sanno rispondere
```

Sta fuori da `app/Domain/` di proposito: queste chiavi esistono solo perché lo storage è
chiave/valore. È persistenza, non dominio — il motore non sa che le aliquote siano mai state righe.

Sei modelli Eloquent in `app/Models/`, due seeder: `TaxYear2026Seeder` (anno, 20 costanti,
scaglioni IRPEF e cuneo) e `TaxPlaces2026Seeder` (8 regioni **tutte con scaglioni**, 8 comuni).

**Il seeder riscrive i valori a mano invece di leggerli da `TaxYear2026`.** La duplicazione è
voluta: è partita doppia. Se il seeder copiasse dalla classe, i due coinciderebbero per
costruzione e il test di confronto non potrebbe intercettare l'aliquota digitata male che
esiste per intercettare.

Verificato dal database: config identica a `TaxYear2026::config()`, e RAL 35.000 commercio
14 mensilità → **25.967,22 €**, lo stesso numero dell'implementazione TypeScript.

`TaxYear2026` ora è solo il riferimento nei test.

**Il repository si rifiuta di rispondere** se manca un dato: anno non pubblicato, comune
sconosciuto, costante assente, regione senza scaglioni, comune ad aliquote proprie. Ognuna di
queste poteva essere uno zero di default — ed è esattamente il fallimento che il progetto vuole
evitare, perché il risultato sembrerebbe uno stipendio, plausibile al centesimo e sbagliato. Con
tutte le regioni seedate quel guardrail non ha più un caso reale su cui appoggiarsi, quindi il
test lo simula rimuovendo a mano gli scaglioni del Lazio.

### Tutte e 8 le regioni, tutte e 8 le città calcolano

Le 7 regioni che mancavano (Lazio, Campania, Emilia-Romagna, Toscana, Puglia, Veneto, Sicilia)
sono state prese dall'endpoint MEF regione per regione
(`addregirpef.php?reg=<id>`, un id per regione, elenco in `sceltaregione.htm`) — la stessa fonte
già usata per i comuni. Verificato prima su Lombardia: torna esattamente 1,23 · 1,58 · 1,72 ·
1,73 %, gli stessi valori già confermati in `TaxYear2026`; solo dopo mi sono fidato dell'endpoint
per le altre sette.

Due correzioni rispetto a quello che si trova cercando in giro:

- **Lazio** scatta al 3,33 % già sopra i 15.000 € di imponibile, non sopra i 28.000 come
  riportano diverse fonti secondarie — MEF traccia la soglia a 15.000.
- **Sicilia** è aliquota unica 1,23 % senza eccezioni. Una fonte trovata prima parlava di un
  1,73 % sotto i 28.000, probabilmente contaminata dalla legge del Lazio: entrambe le leggi
  regionali si chiamano "L.R. 20 del 31/12/2025", stessa data e stesso numero, regioni diverse.
- **Puglia** ha un'aliquota più alta (1,33–3,33 %) di quella riportata da vari calcolatori
  online (1,23–2,23 %): un decreto del commissario ad acta del 28/05/2026 l'ha rideterminata
  per coprire il disavanzo sanitario regionale, e le fonti secondarie non l'hanno ancora recepito.

Veneto e Sicilia sono aliquota unica (un solo scaglione, nessuna fetta da tagliare); le altre
cinque seguono le stesse tre soglie di IRPEF e Lombardia (15.000 · 28.000 · 50.000).

**Non modellate:** l'aliquota ridotta 0,9 % per disabilità in Veneto, le detrazioni per figli a
carico di Lazio, Campania e Sicilia — sono tutte agevolazioni legate a familiari a carico, già
fuori scope per lo stesso motivo dichiarato in `CLAUDE.md`.

### Risorse Filament — completo, 2 test in più

Una risorsa sola, `TaxYearResource`, con le altre quattro tabelle come schede annidate nella
pagina di modifica — non cinque voci di menu separate, perché tutte dipendono da un anno e
questo lo rende esplicito invece di lasciarlo a un filtro:

```
app/Filament/Resources/TaxYears/
├── TaxYearResource.php              anno, etichetta, pubblicato il, note
└── RelationManagers/
    ├── ConstantsRelationManager     le 20 costanti, chiave come select su TaxConstantKey
    ├── RegionsRelationManager       le 8 regioni, badge di conteggio scaglioni/comuni
    ├── MunicipalitiesRelationManager  gli 8 comuni, regione come select, non testo libero
    └── BracketsRelationManager      tutte le fasce, `owner_id` compare solo se il tipo lo richiede
```

Locale e timezone erano ancora `en`/`UTC` — lo notava già questo file, sotto "cose da non
dimenticare". Sistemato qui perché è la prima volta che tocca davvero l'interfaccia: si vedeva
subito ("New anno fiscale" invece di "Nuovo"). Ora `it`/`Europe/Rome` in `.env`, `.env.example`
e `config/app.php`.

Tre decisioni non ovvie:

- **La chiave di una costante non è testo libero.** È un select sui casi di `TaxConstantKey`,
  come per il seeder: una chiave che il motore non conosce non può nemmeno essere digitata.
- **La chiave di una costante non si modifica dopo la creazione.** Cambiarla equivarrebbe a
  spostare un valore su un'altra costante — bloccato in form, non solo sconsigliato.
- **La creazione resta aperta**, sia per le costanti che per le altre tabelle. `CLAUDE.md` dice
  esplicitamente che aggiornare il 2027 dev'essere "un inserimento di dati, non un deploy":
  bloccare la creazione avrebbe contraddetto il motivo per cui Filament esiste in questo
  progetto.

Verificato dal vivo nel browser, non solo nei test: login, le quattro schede, il campo
`owner_id` degli scaglioni che compare solo per `regional_surtax`/`municipal_surtax` e si
popola con le regioni giuste.

Il test mancante nella checklist ("un utente senza `is_admin` non vede nulla") ora è concreto,
non solo sul metodo `canAccessPanel()` isolato: un utente loggato non admin che chiede
`/admin/tax-years` riceve 403, un admin 200. In `tests/Feature/AdminAccessTest.php`.

### API — completo, 26 test in più

Fatta in una notte, senza controllo — la prossima sessione la riguarda per prima cosa. Otto
rotte, tutte in `routes/api.php`:

```
POST   /api/register · /api/login · /api/logout          Sanctum, cookie di sessione
GET    /api/tax-years/{year}                              costanti + scaglioni nazionali, con fonte
POST   /api/simulations                                   calcola, salva, restituisce token + risultato
GET    /api/simulations/{token}                            legge lo snapshot, pubblica
GET    /api/me/simulations · DELETE .../{id}               con auth, scoping per proprietario
```

`StoreSimulationRequest`, `RegisterRequest`, `LoginRequest` in `app/Http/Requests/`;
`SimulationController`, `TaxYearController`, `AuthController` in
`app/Http/Controllers/Api/`; `SimulationResource`, `TaxYearDetailResource` in
`app/Http/Resources/`.

**L'anno non è mai un input.** Il contratto originale in questo file non menzionava il comune,
ma il motore lo richiede — l'ho aggiunto come quarto campo obbligatorio. L'anno invece non è
mai stato nel contratto ed è giusto così: `TaxYearRepository::currentYear()` lo risolve da solo
(il massimo anno pubblicato), così una richiesta non può chiedere un anno più vecchio e più
favorevole. Il comune è validato contro le sole città dell'anno corrente, non contro l'intero
storico.

`SalaryBreakdown` e le classi che lo compongono sono tutte `readonly` con proprietà pubbliche:
`json_encode` le serializza da sole, comprese le enum, senza bisogno di un trasformatore scritto
a mano. Il `result` salvato è esattamente l'oggetto restituito subito dopo il calcolo — nessuna
differenza tra quello che vedi al momento e quello che rileggi dopo, come vuole lo snapshot.

**Due bug veri, trovati solo testando su HTTP reale — nessuno dei due l'ha preso Pest:**

1. Il middleware statale di Sanctum si attiva solo se la richiesta ha un header
   `Referer`/`Origin` che combacia con un dominio noto. La lista di default copriva solo la
   porta di `APP_URL` (8000); il dev server di questo progetto gira su 5174
   (`.claude/launch.json`). Corretto abilitando `Sanctum::currentRequestHost()` in
   `config/sanctum.php` — coerente con l'architettura same-origin: qualunque host serva la
   richiesta è per definizione quello giusto, e il CSRF resta comunque obbligatorio.
2. Un ospite che chiede una rotta protetta **senza** header `Accept: application/json` mandava
   in crash l'app con 500 invece di un 401 pulito: il default di Laravel prova a fare redirect
   a una route `login` che qui non esiste, perché non c'è nessuna pagina di login server-side.
   Corretto con `$middleware->redirectGuestsTo(fn () => null)` in `bootstrap/app.php`. Un
   client reale che dimentica quell'header — e non è garantito che axios/fetch lo mandi sempre
   — avrebbe preso lo stesso crash.

Verificato via `curl` con il giro CSRF completo (`/sanctum/csrf-cookie` → `X-XSRF-TOKEN`), non
solo nei test: registrazione, simulazione da loggato, lettura di `/api/me/simulations`, logout,
e che dopo il logout la stessa rotta torni un 401 pulito. I dati di prova creati durante questa
verifica sono stati ripuliti dal database di sviluppo.

Corretti anche, incontrati per strada:

- `SESSION_DRIVER=database` senza dubbio sulla tabella `sessions` — falso allarme, la tabella
  c'era già (nella migration `users`, come fa lo scaffold di Laravel), il comando `sqlite3` non
  era installato e la verifica falliva in silenzio.
- `bootstrap/app.php` non aveva `$middleware->statefulApi()`: senza, Sanctum non avrebbe mai
  autenticato via cookie di sessione, indipendentemente da tutto il resto.
- `App\Models\User` non aveva `HasApiTokens` né una relazione `simulations()` — aggiunte
  entrambe.

---

## Da fare, in ordine

### 1. Frontend Quasar

- [x] Progetto Quasar CLI in `frontend/`, build dentro `backend/public`
- [x] Route catch-all in Laravel per servire la SPA
- [x] Landing one-page senza scroll
- [x] Wizard: RAL → mensilità → settore → luogo → risultato
- [x] Header condiviso, login e registrazione, "Le mie simulazioni"
- [ ] Donut col buco che fa da display (hover scrive al centro) — oggi c'è una barra
      segmentata con legenda, che assolve alla stessa regola ("il colore non porta mai da solo
      il significato") ma non è ancora il donut descritto qui sotto
- [ ] Donut col buco che fa da display (hover scrive al centro)
- [ ] Loader didattico ~800 ms coi passaggi reali
- [ ] "Riga per riga" con tabella per scaglione
- [ ] "Le mie simulazioni"

`/s/{token}` **non è di questo blocco**: per `CLAUDE.md` è renderizzata server-side da Blade,
non dalla SPA — serve alle meta OpenGraph. È backend, va con la route catch-all sopra, non con
Quasar. L'API che le serve entrambe (`GET /api/simulations/{token}`) c'è già.

#### Impianto frontend, già fatto

Quasar 2.28 · Vue 3 · TypeScript · Pinia · pnpm. Dev server sulla **9200**, con proxy di `/api`,
`/sanctum` e `/admin` sulla 5174: anche in sviluppo è same-origin come in produzione, quindi il
cookie di sessione e l'handshake CSRF si comportano già come si comporteranno una volta
compilato.

**Il build non può puntare `distDir` su `backend/public`.** Ci ho provato e ha cancellato
`index.php` e tutti gli asset di Filament: prima di ogni build Quasar esegue
`removeBuildArtifacts()`, che è un `fse.removeSync()` sull'intera `distDir`. Non è
un'impostazione di Vite, quindi `build.emptyOutDir` non la disattiva. Ora `pnpm build` compila
in `dist/spa` e poi copia con `frontend/scripts/copy-to-laravel.mjs`, che rimuove **solo** ciò
che possiede la SPA (`assets/`, `icons/`, `index.html`). Se il server Laravel gira mentre
succede una cosa del genere, va riavviato: il processo tiene aperto il vecchio inode della
document root e risponde 500 a tutto.

Verificato dopo il build: `/` e i deep link servono la SPA, `/admin` resta Filament, `/api`
resta l'API. `backend/public/favicon.ico` risulta modificato in git perché la SPA sovrascrive il
placeholder vuoto di Laravel: è corretto, semmai va tolto dal tracking.

#### Landing — fatta

`src/pages/LandingPage.vue`, una schermata sola. Sfondo Aurora di [Vue Bits](https://vue-bits.dev)
(WebGL via `ogl`, MIT) **vendorizzato a mano** in `src/components/vendor/Aurora.vue`: vue-bits
distribuisce i sorgenti con `jsrepo`, non come pacchetto runtime, e la configurazione dei path di
jsrepo non funzionava. Il file resta verbatim per poterlo risincronizzare; palette e
`prefers-reduced-motion` stanno nel wrapper `AuroraBackdrop.vue`, che sotto reduced-motion **non
avvia proprio il render loop** invece di limitarsi a rallentarlo.

- **Font: Atkinson Hyperlegible Next**, disegnato dal Braille Institute per la leggibilità a
  bassa visione. "Leggibile" è una delle tre parole del brand e il prodotto promette che il
  risultato lo capisca chiunque: il font è quella promessa applicata a sé stessa.
- **Palette:** nero-muschio, accento fosforo, un solo oro per la cifra su cui deve cadere
  l'occhio. Evita tutti e tre gli anti-riferimenti (tabella grigia, gradiente SaaS,
  navy-e-oro). **Contrasti misurati, non stimati:** corpo 16.75:1, secondario 7.65:1, accento
  10.17:1, testo del pulsante 10.17:1.
- **Il titolo usa numeri veri:** 35.000 € lordi → 25.967,22 € netti, lo stesso valore che
  asserisce il test del backend, così la landing non può divergire da quello che calcola il
  motore.

**Un bug trovato e corretto, da ricordare:** `useCountUp` all'inizio partiva da zero e saliva con
`requestAnimationFrame`. Se rAF non parte — scheda in background, prerender, screenshot headless —
il valore a riposo restava **0,00 €**: non un'animazione mancata, ma l'affermazione che il netto è
zero. Ora il valore di partenza è quello finale e l'azzeramento avviene dentro il primo frame:
se quel frame non arriva, sullo schermo c'è sempre stata la cifra giusta.

`PRODUCT.md` in radice è un **puntatore** a `PRODOTTO.md`, non una seconda specifica: serve solo
perché la skill `impeccable` cerca quel nome. Se divergono, vince `PRODOTTO.md`.

#### Tema liquid glass e resto della SPA — fatti

Palette virata al **blu** (era verde muschio): nero-blu, azzurro come primario, un solo ambra
speso sulla cifra su cui deve cadere l'occhio. Sfondo animato **Silk** al posto di Aurora (stesso
`ogl`, nessuna dipendenza nuova).

**Dove il vetro si ferma: da nessuna parte, dal 30/08/2026.** Prima stava su header, landing e
login soltanto, e wizard, risultato e simulazioni salvate usavano `.panel-solid`. Ora il tema
copre tutte le schermate — vedi `PRODOTTO.md`, sezione «Il tema non si ferma più alla CTA», per il
perché il divieto è diventato una soglia di contrasto.

Il vetro ha due profondità: `.glass` all'82% per la cronologia (header, landing, login),
`.glass-panel` al 93% dove si leggono cifre. Contrasti rimisurati sul caso peggiore reale — pixel
più chiaro del canvas Silk campionato **dal canvas** (`rgb(42, 74, 134)`), velo al punto più
trasparente, nessuno sconto per la sfocatura: sul vetro profondo corpo **16,57:1**, secondario
**7,67:1**, azzurro **8,94:1**, ambra **11,48:1**.

`SilkBackdrop` prende una prop `fixed`. Silk dimensiona il canvas su `offsetHeight` del
contenitore: su una pagina che scrolla, un backdrop `inset: 0` renderebbe una superficie WebGL
alta quanto tutto il documento e la porterebbe via con lo scroll.

Il vetro è **CSS** (`backdrop-filter`), non il componente `GlassSurface` di Vue Bits. Quel
componente ottiene la rifrazione con filtri SVG che funzionano **solo su Chromium** — ha lui
stesso il rilevamento e su Safari e Firefox degrada a sfocatura semplice, cioè esattamente il
risultato del CSS. Se serve la rifrazione vera si può vendorizzare, sapendo che la vedrà una
parte dei visitatori.

```
src/
├── components/   AppHeader · SilkBackdrop · GlassPrism · BreakdownRow · vendor/Silk
├── composables/  useCurrency · useCountUp
├── services/     api.ts — un solo posto che parla con Laravel
├── stores/       auth · simulation
└── pages/        Landing · Wizard · Result · Login · SavedSimulations
```

**Il client non calcola niente.** Il wizard raccoglie quattro risposte, le manda e mostra lo
snapshot che torna. Verificato dal browser sul flusso reale: 35.000 commercio 14 mensilità a
Milano → netto **25.967,22 €**, busta **1.910,42 €**, tredicesima **1.521,07 €**, gli stessi
numeri che asserisce il backend.

Il 3D del login è three.js (~600 KB, costo accettato consapevolmente): un **simbolo dell'euro** di
vetro che oscilla e segue il puntatore (`GlassEuro.vue`, prima era un ottaedro). Il glifo è
costruito da primitive — un settore di corona circolare per la C, due rettangoli per le barre —
non estruso da un font: niente asset da caricare e niente questione di licenza sugli outline. Le
tre solide si intersecano di proposito: `ExtrudeGeometry` non fa unione booleana, e in un
materiale con `transmission` quelle superfici interne rifrangono, il che lo fa leggere come un
oggetto di vetro sfaccettato invece che come un adesivo. La rotazione oscilla entro ±0,5 rad
invece di girare: un'estrusione vista di taglio smette di essere un euro.

**Tre bug trovati testando davvero, non solo compilando:**

1. Il canvas 3D restava 300×150. Era montato dentro un contenitore `display:none`, quindi il
   `ResizeObserver` misurava zero e non rimisurava più quando il pannello compariva. Ora sotto i
   60rem il componente **non viene montato affatto**: su telefono non si crea nemmeno un contesto
   WebGL.
2. Gli importi sotto i 10.000 € uscivano senza separatore (`1910,42` accanto a `25.967,22`).
   Non era un errore mio: per l'italiano Intl raggruppa solo da cinque cifre. Forzato, perché
   queste cifre si leggono in colonna e si confrontano con un cedolino.
3. Mancavano due endpoint che il frontend richiedeva: `GET /api/me` (l'header non poteva sapere
   chi fosse loggato senza dedurlo da un'altra rotta) e
   `GET /api/tax-years/{year}/municipalities` — il passo "luogo" non aveva da dove prendere i
   comuni. Il secondo elenca **solo i comuni che calcolano davvero**: offrirne uno la cui regione
   non ha scaglioni sarebbe un vicolo cieco presentato come una scelta.
4. **Nessuna rotta con sessione funzionava dal dev server.** Il proxy in `quasar.config.ts` aveva
   `changeOrigin: true`, che riscrive l'header `Host` a `localhost:5174`: Laravel rispondeva 5174
   a `getHttpHost()` mentre il `Referer` del browser diceva ancora 9200.
   `EnsureFrontendRequestsAreStateful` confronta esattamente quei due, non combaciava mai, e
   **non applicava alcun middleware in silenzio** — né `StartSession` né la verifica CSRF. Quindi
   registrazione e login morivano su «Session store not set on request», `/api/me` rispondeva 401
   a un utente loggato, e `POST /api/simulations` sembrava funzionare solo perché saltava anche il
   controllo CSRF che avrebbe dovuto affrontare. Il commento sopra il proxy prometteva che in
   sviluppo l'handshake si comportasse «come si comporterà una volta compilato»: era proprio
   `changeOrigin` a rendere falsa quella promessa. Ora è `false`, e la verifica passa attraverso
   la 9200 e non solo diretta sulla 5174 — che è il motivo per cui prima sembrava a posto.
5. **La tabella "riga per riga" non era una tabella.** `BreakdownRow` chiamava il suo blocco
   `.row`, che è l'utility flexbox globale di Quasar: il `<tr>` ereditava `display: flex` e
   l'intera tabella usciva dal table formatting context. Ogni riga dimensionava per conto suo
   etichetta e importo, e la colonna delle cifre veniva fuori sfrangiata — su un prodotto che
   chiede di confrontare importi in colonna. Bloccato per un anno in bella vista, visibile solo
   guardando la pagina. Il blocco ora si chiama `.line`, e il `<th>` è tornato una cella vera con
   il flex spostato dentro (anche lì `display: flex` su una cella la toglieva dalla tabella).

#### Salvataggio e adozione — fatto, 6 test in più

Una simulazione è **sempre** salvata, anche da ospite: `POST /api/simulations` scrive comunque la
riga. Mancava solo che si vedesse, e mancava il pezzo che la rendeva utile.

Il buco vero era che una simulazione fatta da ospite nasce con `user_id = null` e ci restava per
sempre: chi si registrava dopo si ritrovava il link nella barra degli indirizzi e nessun modo di
metterlo nella lista per cui l'account esiste. Aggiunto
`POST /api/me/simulations/{token}/claim`, che attacca al richiedente una simulazione senza
proprietario.

`SimulationResource` espone due booleani, `mine` e `claimable`, calcolati **per chi chiede**. La
pagina risultato ne ricava i suoi tre stati: «✓ è nelle tue simulazioni», «Salva nelle mie
simulazioni», «Accedi per salvarla». Il link di login porta `?ritorna=`, validato contro gli
open redirect (solo path che iniziano con `/` e non con `//`).

Una simulazione già di qualcun altro risponde **404 e non 403**: chi chiama possiede solo un
token, e rispondere «è di un altro» trasformerebbe un token indovinato in un modo per sondare
quali sono presi.

Un dettaglio che sembrava innocuo: `ResultPage` riusava la copia in memoria se il token
combaciava. Ma `mine` è calcolato per chi ha chiesto, quindi la copia presa da ospite dice
`mine: false` per sempre — compreso al ritorno dal login, cioè esattamente il momento in cui la
pagina deve offrire di salvare. Ora la copia si riusa solo finché quei flag possono essere giusti.

### 2. Deploy

- [ ] Scegliere l'host — criterio: **non deve dormire**. Laravel Cloud o Oracle Always Free
- [ ] `sudo dnf install php-pgsql` se in produzione si va su Postgres
- [ ] `php artisan user:promote` sull'utente in produzione

La procedura passo passo sta in [DEPLOY.md](DEPLOY.md).

---

## Dati fiscali: cosa c'è e cosa manca

### Le 8 città seedate, verificate sull'elenco MEF — tutte calcolano

| Città | Aliquota | Esenzione | Delibera |
| --- | --- | --- | --- |
| Milano | 0,8 % | 23.000 | n. 46 · 28/09/2020 |
| Roma | 0,9 % | 14.000 | n. 186 · 19/12/2024 |
| Napoli | 1,0 % | 12.000 | n. 143 · 29/12/2023 |
| Bologna | 0,8 % | 15.000 | n. 354 · 22/12/2016 |
| Firenze | 0,2 % | 25.000 | n. 47 · 28/07/2014 |
| Bari | 0,8 % | 15.000 | n. 42 · 31/07/2012 |
| Venezia | 0,8 % | 10.000 | n. 67 · 20/12/2023 |
| Palermo | 1,014 % | — | n. 6 · 25/02/2025 |

**Fonte:** elenco generale MEF, un CSV per anno d'imposta.

```
https://www1.finanze.gov.it/finanze2/dipartimentopolitichefiscali/fiscalitalocale/addirpef_newDF/download/download.php?anno=2025
```

### Torino e Genova: fuori per ora

Usano **scaglioni comunali propri**, che il motore non gestisce: oggi il comunale è
un'aliquota unica dietro un'esenzione.

Servono ~15 righe in `SurtaxesCalculator` più i test. Lo schema è già pronto: `tax_brackets`
accetta `kind = municipal_surtax` con `owner_id` sul comune, e `tax_municipalities.rate` è
nullable proprio per questo.

### Le regioni: le 8 che servono ci sono, le altre 12 no

Lombardia, Lazio, Campania, Emilia-Romagna, Toscana, Puglia, Veneto e Sicilia hanno tutte gli
scaglioni, presi dall'endpoint MEF regione per regione (non un CSV come per i comuni, una
pagina HTML per regione):

```
https://www1.finanze.gov.it/finanze2/dipartimentopolitichefiscali/fiscalitalocale/addregirpef/addregirpef.php?reg=<id>
```

L'elenco degli id sta in `sceltaregione.htm`. Sono le uniche 8 che servono: coprono tutte le
città seedate. Piemonte e Liguria — che servirebbero a Torino e Genova — non sono state prese
perché quelle due città sono fuori per un motivo diverso, vedi sopra: non è una regione mancante,
è il motore che non gestisce ancora il comunale a scaglioni.

Le altre 12 regioni (Abruzzo, Basilicata, Calabria, Friuli-Venezia Giulia, Liguria, Marche,
Molise, Piemonte, Sardegna, Umbria, più le province di Trento e Bolzano) non hanno né regione né
città seedate: mancano entrambe, non solo gli scaglioni.

### Perché non importiamo tutti i 7.897 comuni

Valutato e scartato con misure sul file vero, non a naso:

1. **Il file 2026 è vuoto per il 61 % dei comuni** — 4.809 su 7.897 riportano `0*` perché non
   hanno ancora deliberato, **Milano compresa**. Servirebbe una catena di ripiego
   2026 → 2025 → 2024 comune per comune.
2. **Gli scaglioni sono testo libero.** Le colonne `FASCIA` contengono frasi come
   *«Esenzione per redditi imponibili fino a euro 12.000,00»*. Sono 1.580 comuni multialiquota
   e 1.275 con esenzione, ognuno con la sua formulazione, più un `FLAG_NUOVA` a sette valori
   che distingue i regimi.

Dati raschiati da HTML e non letti da una delibera sarebbero l'unica parte del progetto non
difendibile — l'opposto di quello che il progetto vuole dimostrare. L'importatore resta una
v2 credibile: la fonte c'è, il percorso è noto, gli ostacoli sono misurati.

---

## Cose da non dimenticare

- **I codici catastali degli 8 comuni non vengono dal CSV MEF**, li ho scritti io (F205, H501,
  D612…). La colonna è nullable e non la usa ancora nessuno — serve a un futuro importatore —
  ma vanno ricontrollati prima di farci affidamento.
- `backend/CLAUDE.md` e `backend/AGENTS.md` sono lo stub di Laravel che chiede di installare
  **Laravel Boost**. Deciso di installarlo ma non ancora fatto; se si rinuncia, vanno
  cancellati perché contengono istruzioni di setup che confondono ogni sessione.
- Il token GitHub va rinnovato con `gh auth login -h github.com` se il push fallisce: tutti
  gli host di deploy partono da un repo GitHub.
