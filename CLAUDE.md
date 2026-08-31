# RalSimulator

Simulatore da RAL a stipendio netto per l'anno d'imposta 2026, con salvataggio e condivisione
delle simulazioni.

La priorità del progetto è essere **in controllo delle logiche**, non coprire superficie: il
motore fiscale, i suoi test e le fonti citate valgono più di qualsiasi cosa lato interfaccia.
Un numero che nessuno sa difendere è peggio di una funzionalità che manca.

> Le linee guida di sviluppo (SOLID, naming, clean code, testing) arrivano dal `CLAUDE.md`
> globale. Qui c'è solo ciò che è specifico di questo progetto.

**Prima di lavorare, leggi [README.md](README.md):** avvio, database, contratto API, sessione,
struttura del frontend, pannello Filament e le regole del calcolo.

**Come si lavora qui: un file alla volta.** Nome, posizione, cosa fa e perché in poche righe,
poi si aspetta l'ok, poi si scrive. Vale anche per i file piccoli.

---

## Il prodotto

Una pagina di presentazione porta al calcolatore. L'utente inserisce la RAL, dichiara mensilità
e settore, e ottiene il netto annuo e mensile con la scomposizione completa delle trattenute.

Da lì può **salvare** la simulazione, **condividerla** con un link pubblico, e — se registrato —
ritrovare le proprie simulazioni.

Il pubblico non è un consulente del lavoro: è chi ha ricevuto un'offerta e vuole capire quanto
gli resta. Il linguaggio dell'interfaccia lo rispecchia. Il dettaglio tecnico c'è, ma sta dietro
una progressive disclosure, non davanti.

---

## Architettura: una sola applicazione, un solo dominio

Il vincolo che governa tutto: **si deploya una cosa sola.**

```
frontend/          SPA Quasar (Quasar CLI)
    └── build ──►  backend/public/
backend/           Laravel — API, motore fiscale, auth, admin
README.md          avvio, architettura, API, sessione, Filament, il calcolo
```

Quasar builda in file statici, Laravel li serve. Una route catch-all restituisce `index.html`
per tutto ciò che non è `/api` o `/s/{token}`.

| Rotta | Serve |
| --- | --- |
| `/` e tutto il resto | SPA Quasar |
| `/api/*` | API JSON |
| `/s/{token}` | Pagina condivisa renderizzata da Blade |

**Conseguenze volute:**

- **Niente CORS.** Stessa origine, nessuna preflight, nessuna configurazione.
- **Auth con cookie di sessione** via Sanctum, non token in `localStorage`. Same-origin significa
  che il cookie funziona e basta.
- `/s/{token}` è renderizzata server-side apposta: serve per i **meta OpenGraph**, così un link
  condiviso mostra l'anteprima con il netto invece di una pagina vuota.

---

## Le cinque decisioni da difendere

### 1. Il motore fiscale sta in PHP, non nel frontend

Il backend possiede il calcolo. Il frontend non calcola niente: manda input e riceve un
risultato. Fonte unica di verità, e il backend ha una ragione di esistere che non sia il CRUD.

### 2. Le aliquote stanno nel database, versionate per anno

Non in un file di configurazione. Le tabelle `tax_years`, `tax_brackets` e `tax_constants`
contengono i parametri di legge, e ogni costante porta **`source_url` e `source_label`**.

Due conseguenze che valgono il costo:

- Aggiornare il simulatore per il 2027 è un inserimento di dati, non un deploy.
- La traccia valuta *«le abilità di ricerca delle informazioni rilevanti dalle fonti»*. Con le
  fonti nel database e mostrate accanto ai numeri nell'interfaccia, quel criterio smette di
  essere un'affermazione nel README e diventa un comportamento del prodotto.

### 3. La simulazione salvata conserva uno snapshot

`simulations` memorizza l'input, il **risultato calcolato** e il `tax_year_id` usato. Non si
ricalcola alla lettura.

Una simulazione fatta nel 2026 e riaperta nel 2027 deve mostrare i numeri con cui è stata fatta.
Ricalcolarla con aliquote nel frattempo cambiate significherebbe che un link condiviso cambia
contenuto sotto i piedi di chi lo ha ricevuto.

### 4. Il calcolo è pubblico, il salvataggio no

Simulare non richiede registrazione: chi arriva dalla homepage ottiene il suo numero senza
attriti. La registrazione serve solo a **ritrovare** le simulazioni.

Una simulazione salvata da un utente anonimo esiste comunque, identificata dal suo token
condivisibile.

### 5. Il pannello admin è Filament

Le tabelle fiscali si gestiscono da Filament, non da un CRUD scritto a mano. Non è pigrizia:
è la dimostrazione concreta che il sistema è parametrico e non ha i numeri cuciti addosso — che
per un'azienda che fa buste paga è esattamente la domanda che verrà.

---

## Backend

**Stack:** Laravel · Sanctum · Filament · Pest · SQLite in sviluppo.

### Il dominio fiscale

La sezione «I calcoli» del [README](README.md#i-calcoli) tiene le costanti, l'ordine delle
operazioni e i tre gradini. **Le fonti primarie stanno nel database**, in `source_url` e
`source_label` accanto a ogni costante: è lì che si verifica un valore, non in un file.

I casi di verifica con i valori attesi sono la suite Pest — `backend/tests/`, 131 test. Chi
tocca il motore parte da quelli.

La specifica estesa era in `docs/FISCO-2026.md`, rimossa il 30/08/2026 accorpando la
documentazione nel README. Resta in git: `git show 7c8edf1:docs/FISCO-2026.md`.

Regole che non si negoziano:

- Il calcolo è **puro**: input e configurazione dentro, risultato fuori. Nessuna query, nessuna
  data di sistema, nessuno stato dentro il motore. Le costanti si caricano dal DB **prima** e si
  passano al calcolatore.
- I **totali annui sono la verità**, le buste si derivano da quelli. Mai il contrario.
- L'IRPEF netta non è mai negativa; le detrazioni in eccesso si perdono, non generano credito.
- Le somme esenti **non sono una fetta della RAL**: si aggiungono al netto. Nessun calcolo e
  nessun grafico può trattarle come una porzione del lordo.
- I tre gradini (8.500 · 15.000 · 23.000 di imponibile) sono nelle norme. Un test percorre tutto
  l'arco dei redditi e pretende che ogni calo del netto cada su una di quelle soglie.

### Contratto API

| Metodo | Rotta | Auth | Cosa fa |
| --- | --- | --- | --- |
| `POST` | `/api/simulations` | no | Calcola, salva, restituisce token e risultato |
| `GET` | `/api/simulations/{token}` | no | Legge una simulazione dallo snapshot |
| `GET` | `/api/me/simulations` | sì | Le simulazioni dell'utente |
| `DELETE` | `/api/me/simulations/{id}` | sì | Elimina una propria simulazione |
| `GET` | `/api/tax-years/{year}` | no | Costanti e fonti, per il dettaglio riga per riga |
| `POST` | `/api/register` · `/api/login` · `/api/logout` | — | Sanctum |

L'input di una simulazione è sempre e solo: `gross_annual_salary`, `monthly_payments_count`
(12 · 13 · 14), `sector` (`commerce` · `industry`). Validato in una FormRequest, mai nel motore.

---

## Frontend

**Stack:** Quasar CLI (SPA) · Vue 3 · TypeScript · Pinia.

### Le schermate

1. **Landing** — one page, senza scroll. Spiega cos'è e come funziona, e porta al calcolatore.
2. **Wizard in quattro passi** — RAL → mensilità → settore → risultato. Quattro e non tre:
   mensilità e settore sono domande diverse e meritano schermate diverse.
3. **Risultato** — netto annuo e mensile, donut della ripartizione, griglia delle buste,
   dettaglio riga per riga, assunzioni dichiarate.
4. **Le mie simulazioni** — elenco per utenti registrati.
5. **`/s/{token}`** — la simulazione condivisa, in sola lettura.

### Decisioni di interfaccia

- **Il donut ha il buco e il buco è il display.** A riposo il centro mostra il netto; l'hover su
  una fetta ci scrive dentro etichetta, importo e percentuale. Niente tooltip fluttuante: niente
  posizionamento da gestire, funziona al tap, non esce mai dai bordi.
- **Le fette dividono la RAL e nient'altro.** Resta a te · contributi · IRPEF · addizionali. Le
  somme esenti stanno in un blocco a parte, introdotte da `+` e chiuse da un `=` col totale
  effettivo. Sommarle dentro "resta a te" farebbe dividere alla barra più di quanto promette.
- **Il colore non porta mai da solo il significato.** Ogni voce ha etichetta e importo accanto
  alla fetta, e ogni riga del dettaglio ha un operatore `−` `+` `=` che gli screen reader leggono
  in parole.
- **Il riga per riga usa tabelle vere.** In particolare l'IRPEF lorda va spiegata con una riga
  per scaglione: imponibile nella fascia × aliquota = imposta. Serve un endpoint che restituisca
  la scomposizione per scaglione, non solo il totale.
- **Il loader spiega invece di riempire.** Il calcolo è rapido; l'attesa si giustifica solo se
  mostra i passaggi reali (contributi → scaglioni → detrazioni → addizionali).
- Le mensilità aggiuntive vanno mostrate **calcolate davvero**, non come media mensile: rendono
  meno di una busta ordinaria a parità di lordo, ed è una delle cose che la gente non sa.

---

## Fuori scope

Da dire, non da costruire:

- Costo azienda: si calcola solo la quota a carico del dipendente
- TFR
- Familiari a carico e oneri detraibili
- Comuni e regioni diversi da Milano e Lombardia — **lo schema deve permetterlo**, l'interfaccia
  non li espone
- Export PDF
- Redditi diversi da lavoro dipendente
- Anni d'imposta diversi dal 2026 — anche qui lo schema è pronto, i dati no
