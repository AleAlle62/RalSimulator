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

### Database — schema creato, vuoto

Sei tabelle migrate, nessun dato dentro:

| Tabella | Contiene |
| --- | --- |
| `tax_years` | anno, etichetta, `published_at`, note |
| `tax_regions` | nome regione + fonte (gli scaglioni stanno in `tax_brackets`) |
| `tax_municipalities` | nome, provincia, codice catastale, aliquota, soglia esenzione, **delibera** e data |
| `tax_brackets` | tutte le liste di scaglioni: `kind` + `owner_id` + `position` |
| `tax_constants` | le 22 costanti, chiave/valore, con fonte |
| `simulations` | token, utente (null), input, `result` JSON come **snapshot** |

`source_url` e `source_label` su brackets, constants, regions e municipalities.

---

## Da fare, in ordine

### 1. Seeder e repository — il prossimo blocco

- [ ] Enum PHP con le 22 chiavi ammesse di `tax_constants`
- [ ] Modelli Eloquent per le sei tabelle
- [ ] Seeder che versa i valori di `TaxYear2026` più le 10 città
- [ ] `TaxYearRepository` che ricostruisce un `TaxYearConfig` leggendo dal DB
- [ ] **Test di confronto**: la config dal database dev'essere identica a `TaxYear2026::config()`.
      È quello che intercetta un'aliquota digitata male nel seeder.

Dopo questo, `TaxYear2026` smette di essere la fonte di produzione e resta solo come
riferimento nei test.

### 2. Risorse Filament

- [ ] CRUD su `tax_years`, `tax_regions`, `tax_municipalities`, `tax_brackets`, `tax_constants`
- [ ] Test: un utente senza `is_admin` non vede nulla

### 3. API

- [ ] `POST /api/simulations` — calcola, salva, restituisce token e risultato
- [ ] `GET /api/simulations/{token}` — legge lo snapshot
- [ ] `GET /api/me/simulations` · `DELETE /api/me/simulations/{id}` — con auth
- [ ] `GET /api/tax-years/{year}` — costanti e fonti, per il "riga per riga"
- [ ] Registrazione e login via Sanctum, cookie di sessione same-origin
- [ ] FormRequest per validare l'input: **il motore non valida**, è compito del confine

### 4. Frontend Quasar

- [ ] Progetto Quasar CLI in `frontend/`, build dentro `backend/public`
- [ ] Route catch-all in Laravel per servire la SPA
- [ ] Landing one-page senza scroll
- [ ] Wizard: RAL → mensilità → settore → luogo → risultato
- [ ] Donut col buco che fa da display (hover scrive al centro)
- [ ] Loader didattico ~800 ms coi passaggi reali
- [ ] "Riga per riga" con tabella per scaglione
- [ ] "Le mie simulazioni" · pagina `/s/{token}` con meta OpenGraph

### 5. Deploy

- [ ] Scegliere l'host — criterio: **non deve dormire**. Laravel Cloud o Oracle Always Free
- [ ] `sudo dnf install php-pgsql` se in produzione si va su Postgres
- [ ] `php artisan user:promote` sull'utente in produzione

---

## Dati fiscali: cosa c'è e cosa manca

### Le 10 città, verificate sull'elenco MEF

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

### Le 20 regioni: solo Lombardia

**Lombardia** è verificata (1,23 · 1,58 · 1,72 · 1,73 %). Le altre 19 vanno prese a mano.

Per le regionali **non esiste un CSV**: l'endpoint analogo a quello comunale risponde 200 ma
restituisce zero byte per ogni anno provato. Resta la consultazione HTML:

```
https://www1.finanze.gov.it/finanze2/dipartimentopolitichefiscali/fiscalitalocale/addregirpef/sceltaregione.htm
```

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
- `backend/CLAUDE.md` e `backend/AGENTS.md` sono lo stub di Laravel che chiede di installare
  **Laravel Boost**. Deciso di installarlo ma non ancora fatto; se si rinuncia, vanno
  cancellati perché contengono istruzioni di setup che confondono ogni sessione.
- Il token GitHub va rinnovato con `gh auth login -h github.com` se il push fallisce: tutti
  gli host di deploy partono da un repo GitHub.
