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

---

## Da fare, in ordine

### 1. Risorse Filament

- [ ] CRUD su `tax_years`, `tax_regions`, `tax_municipalities`, `tax_brackets`, `tax_constants`
- [ ] Test: un utente senza `is_admin` non vede nulla

### 2. API

- [ ] `POST /api/simulations` — calcola, salva, restituisce token e risultato
- [ ] `GET /api/simulations/{token}` — legge lo snapshot
- [ ] `GET /api/me/simulations` · `DELETE /api/me/simulations/{id}` — con auth
- [ ] `GET /api/tax-years/{year}` — costanti e fonti, per il "riga per riga"
- [ ] Registrazione e login via Sanctum, cookie di sessione same-origin
- [ ] FormRequest per validare l'input: **il motore non valida**, è compito del confine

### 3. Frontend Quasar

- [ ] Progetto Quasar CLI in `frontend/`, build dentro `backend/public`
- [ ] Route catch-all in Laravel per servire la SPA
- [ ] Landing one-page senza scroll
- [ ] Wizard: RAL → mensilità → settore → luogo → risultato
- [ ] Donut col buco che fa da display (hover scrive al centro)
- [ ] Loader didattico ~800 ms coi passaggi reali
- [ ] "Riga per riga" con tabella per scaglione
- [ ] "Le mie simulazioni" · pagina `/s/{token}` con meta OpenGraph

### 4. Deploy

- [ ] Scegliere l'host — criterio: **non deve dormire**. Laravel Cloud o Oracle Always Free
- [ ] `sudo dnf install php-pgsql` se in produzione si va su Postgres
- [ ] `php artisan user:promote` sull'utente in produzione

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

- **Timezone e locale** sono ancora `UTC` e `en`: vanno su `Europe/Rome` e `it` prima di
  toccare le date.
- **I codici catastali degli 8 comuni non vengono dal CSV MEF**, li ho scritti io (F205, H501,
  D612…). La colonna è nullable e non la usa ancora nessuno — serve a un futuro importatore —
  ma vanno ricontrollati prima di farci affidamento.
- `backend/CLAUDE.md` e `backend/AGENTS.md` sono lo stub di Laravel che chiede di installare
  **Laravel Boost**. Deciso di installarlo ma non ancora fatto; se si rinuncia, vanno
  cancellati perché contengono istruzioni di setup che confondono ogni sessione.
- Il token GitHub va rinnovato con `gh auth login -h github.com` se il push fallisce: tutti
  gli host di deploy partono da un repo GitHub.
