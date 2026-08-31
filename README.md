# RalSimulator

Calcolatore da **RAL lorda** a **stipendio netto**, anno d'imposta 2026. Mostra contributi, IRPEF,
addizionali e somme esenti voce per voce, con le singole buste paga. Le simulazioni si salvano e
si condividono con un link.

**Esempio:** RAL 35.000 €, commercio, 14 mensilità, Milano → **25.967,22 €** netti all'anno,
busta ordinaria **1.910,42 €**, tredicesima **1.521,07 €**.

```
frontend/   SPA Quasar (Vue 3)  ──build──►  backend/public/
backend/    Laravel — API, motore fiscale, login, pannello admin
```

---

## Avviare il progetto

Servono **due terminali**.

```bash
# 1. backend
cd backend && composer install && php artisan migrate --seed && php artisan serve --port=5174

# 2. frontend
cd frontend && pnpm install && pnpm dev
```

Apri **http://localhost:9200**. Non aprire la 5174: quella è solo Laravel, senza interfaccia.

Test: `cd backend && ./vendor/bin/pest` (121 test).

---

## Il proxy

Il frontend gira sulla 9200, Laravel sulla 5174. Sono due porte diverse, quindi due origini
diverse — e il cookie di login non funzionerebbe.

Il proxy in `quasar.config.ts` risolve questo: tutto ciò che inizia per `/api`, `/sanctum` o
`/admin` viene **girato in silenzio** alla 5174. Per il browser esiste una sola origine, la 9200,
esattamente come sarà in produzione dove Laravel serve tutto da solo.

> ⚠️ `changeOrigin` deve restare **`false`**. A `true` il proxy cambia l'header `Host` e Laravel
> non riconosce più le richieste come "sue": salta sessione e CSRF, e il login smette di funzionare
> con un errore che sembra non c'entrare nulla («Session store not set on request»).

---

## Come si avvia il pannello admin (Filament)

È già dentro Laravel, non è un progetto a parte. Vai su **http://localhost:9200/admin**.

Serve un utente con `is_admin = true`. Chi si registra dal sito **non** ce l'ha: è voluto,
altrimenti chiunque entrerebbe nel pannello.

```bash
php artisan make:filament-user        # crea un utente, se non ne hai
php artisan user:promote tua@email    # senza questo, /admin risponde 403
```

---

## Il database

Sei tabelle. L'idea di fondo: **le aliquote sono dati, non codice.**

| Tabella | Cosa contiene |
| --- | --- |
| `tax_years` | l'anno d'imposta |
| `tax_regions` | le regioni |
| `tax_municipalities` | comuni: aliquota, soglia di esenzione, delibera |
| `tax_brackets` | tutti gli scaglioni (IRPEF, cuneo, addizionali) |
| `tax_constants` | le 20 costanti chiave/valore |
| `simulations` | le simulazioni salvate |

Tre cose da sapere:

1. Ogni costante ha `source_url` e `source_label`: **la fonte di legge sta accanto al numero**.
2. Aggiornare al 2027 è un inserimento di dati dal pannello, non una modifica al codice.
3. `simulations.result` è una **fotografia**, non una cache: non si ricalcola mai. Una simulazione
   riaperta fra un anno mostra i numeri di oggi, altrimenti un link condiviso cambierebbe da solo.

Se manca un dato (comune sconosciuto, regione senza scaglioni) il sistema **si rifiuta di
rispondere** invece di mettere zero. Uno zero darebbe un risultato che sembra uno stipendio ed è
sbagliato.

---

## Le chiamate nel backend

Il giro è sempre lo stesso:

```
routes/api.php → FormRequest (valida) → Controller (coordina) → app/Domain/ (calcola) → Resource (serializza in JSON)
```

| Metodo | Rotta | Login? |
| --- | --- | --- |
| `POST` | `/api/simulations` | no |
| `GET` | `/api/simulations/{token}` | no |
| `GET` | `/api/tax-years/{anno}` · `/{anno}/municipalities` | no |
| `GET` | `/api/me/simulations` | sì |
| `POST` | `/api/me/simulations/{token}/claim` | sì |
| `DELETE` | `/api/me/simulations/{id}` | sì |
| `POST` | `/api/register` · `/api/login` · `/api/logout` | — |

Due regole:

- **`app/Domain/` non conosce Laravel.** Dentro ci sono solo classi PHP pure: input e aliquote
  entrano, risultato esce. Niente query, niente data di sistema. Per questo si testa senza
  database.
- **L'anno non si può chiedere.** Lo decide il server, altrimenti si potrebbe chiedere un anno
  vecchio con aliquote più basse.

---

## Le librerie

**Backend** — Laravel 13 · Sanctum (login) · Filament (pannello admin) · Pest (test) · SQLite in
sviluppo.

**Frontend** — Quasar 2 / Vue 3 · TypeScript · Pinia (stato) · `ogl` (sfondo animato) · `three`
(l'euro 3D nella pagina di login).

Non c'è axios: si usa `fetch`, quello nativo del browser. Non c'è una libreria di grafici: la
barra delle proporzioni sono quattro `<div>` con una larghezza in percentuale.

---

## Login e sessione

Si usa un **cookie di sessione**, non un token salvato nel browser. Un token in `localStorage`
sarebbe leggibile da qualsiasi script malevolo iniettato nella pagina; il cookie no.

**Lato backend** — Laravel crea la sessione al login e la lega a un cookie. Sanctum aggiunge un
controllo: accetta la sessione **solo** se la richiesta arriva dal dominio del sito. È il motivo
per cui il proxy qui sopra deve essere configurato bene.

**Lato frontend** — tutto passa da `services/api.ts`, l'unico file che parla con Laravel. Fa tre
cose sempre:

1. manda `Accept: application/json`, altrimenti Laravel prova a redirigere a una pagina di login
   che qui non esiste e va in errore 500;
2. manda i cookie (`credentials: 'same-origin'`);
3. prima di ogni scrittura chiede il **token CSRF** a `/sanctum/csrf-cookie` e lo rimanda come
   header. Lo rilegge ogni volta: il login rigenera la sessione e il token cambia.

**Il CSRF in due righe:** serve a impedire che un altro sito faccia richieste al posto tuo usando
il tuo cookie. Il cookie parte da solo, il token no — quindi solo il nostro sito può mandarlo.

---

## Come funziona il frontend

```
src/
├── pages/        Landing · Wizard · Result · Login · SavedSimulations
├── components/   pezzi riusabili (header, sfondo, riga del dettaglio)
├── composables/  funzioni riusabili (formattazione euro, animazione numeri)
├── services/     api.ts — l'unico posto che chiama Laravel
└── stores/       auth · simulation (Pinia: lo stato condiviso fra pagine)
```

**Il frontend non calcola niente.** Il wizard raccoglie quattro risposte (RAL → mensilità →
settore → città), le manda al backend e mostra quello che torna. I calcoli stanno **solo** in
`backend/app/Domain/`: una verità sola, in un posto solo.

Il percorso: `/` presentazione → `/simulazione` le quattro domande → `/risultato/{token}` →
`/simulazioni` quelle salvate.

**Il salvataggio.** Ogni simulazione viene salvata sempre, anche senza account: resta raggiungibile
dal suo link. Se poi ti registri, il pulsante «Salva» la collega al tuo account (`claim`).

---

## Cambiare le aliquote dal pannello

`/admin` → **Anno fiscale** → apri il 2026. Le altre tabelle sono schede dentro quella pagina,
perché dipendono tutte dall'anno.

| Scheda | Cosa cambi |
| --- | --- |
| Costanti | aliquote contributive, soglie, importi delle detrazioni |
| Comuni | aliquota comunale, soglia di esenzione, delibera |
| Regioni | le regioni e la loro fonte |
| Scaglioni | IRPEF, cuneo, addizionali |

Due limiti messi apposta: la chiave di una costante si sceglie da un elenco (non si scrive a mano,
così non puoi inventarne una che il motore non conosce) e non si può modificare dopo — cambiarla
sposterebbe un valore su un'altra costante.

Per un anno nuovo: crea l'anno, riempi costanti e scaglioni, e **solo alla fine** valorizza
`published_at`. Finché è vuoto l'anno non viene usato.

---

## I calcoli

L'ordine non è casuale: è quello della busta paga vera. I contributi vengono per primi perché
**abbassano la base su cui si calcola tutto il resto**.

```
1. contributi   = RAL x aliquota del settore
2. imponibile   = RAL - contributi
3. IRPEF lorda  = scaglioni progressivi sull'imponibile
4. detrazioni   = detrazione da lavoro + eventuale detrazione "cuneo"
5. IRPEF netta  = IRPEF lorda - detrazioni        (mai sotto zero)
6. addizionali  = regionale + comunale, sull'imponibile
7. somme esenti = bonus cuneo + trattamento integrativo

   NETTO = RAL - contributi - IRPEF netta - addizionali + somme esenti
```

**Le aliquote 2026:**

| Voce | Valore |
| --- | --- |
| Contributi INPS | 9,19 % commercio · 9,49 % industria (la differenza è la CIGS) |
| IRPEF | 23 % fino a 28.000 · 33 % fino a 50.000 · 43 % oltre |
| Detrazione lavoro | 1.955 € fissi sotto 15.000, poi cala fino a zero a 50.000 |
| Cuneo, sotto 20.000 | somma **esente** (7,1 / 5,3 / 4,8 %) |
| Cuneo, sopra 20.000 | **detrazione** di 1.000 €, che si azzera a 40.000 |
| Add. regionale Lombardia | 1,23 % → 1,73 % a scaglioni |
| Add. comunale Milano | 0,8 %, **esente** sotto 23.000 di imponibile |

**Tre cose controintuitive, e sono tutte corrette:**

1. **Le somme esenti non escono dal lordo, ci si aggiungono.** Non sono una fetta della RAL: per
   questo nel grafico stanno fuori dalla barra, altrimenti le percentuali supererebbero il 100 %.
2. **La tredicesima rende meno di una busta normale**, a parità di lordo: le detrazioni sono già
   state usate dalle dodici ordinarie, quindi sulle extra si paga l'aliquota piena.
3. **In tre punti il netto scende se il lordo sale** — a 8.500, 15.000 e 23.000 di imponibile. Non
   sono bug: sono soglie di legge dove un beneficio si perde o si ricalcola. Un test percorre tutte
   le RAL da 1.000 a 300.000 € e pretende che ogni calo cada esattamente su una di queste tre.

Il caso di riferimento (35.000, commercio, Milano) è verificato con un calcolo indipendente su
29 RAL diverse: coincidono al centesimo.

---

## Cosa non è compreso

Costo azienda · TFR · familiari a carico · oneri detraibili · export PDF · redditi diversi da
lavoro dipendente · anni diversi dal 2026 · comuni con scaglioni comunali propri (Torino, Genova).

**Non sostituisce il conteggio di un consulente del lavoro.**
