# Il motore fiscale — anno d'imposta 2026

Questo documento è la **specifica completa** del calcolo da RAL a netto. Contiene ogni costante
con la sua fonte, ogni formula, e i casi di verifica con i valori attesi.

È scritto per essere sufficiente da solo: chi implementa il motore in PHP non deve avere sotto
gli occhi nessun'altra cosa. Le costanti qui dentro diventano il seeder di `tax_years`,
`tax_brackets` e `tax_constants`; i casi di verifica diventano la suite Pest.

Ogni cifra è un parametro di legge del 2026 verificato su fonte primaria. Niente è stimato o
interpolato: quello che non è stato possibile confermare sta in "Limiti noti" alla fine.

---

## 1. Contributi previdenziali INPS, quota a carico del dipendente

**Fonte:** INPS, Circolare n. 6 del 30/01/2026 (valori contributivi dal 01/01/2026).

| Parametro | Valore |
| --- | --- |
| Aliquota commercio | 9,19 % |
| Aliquota industria | 9,49 % |
| Soglia aliquota aggiuntiva IVS | 56.224 € |
| Aliquota aggiuntiva IVS | 1 % sulla parte eccedente |
| Massimale annuo base contributiva | 122.295 € |

I due settori differiscono per lo **0,30 % di CIGS**, che l'industria versa e il commercio no.
La quota IVS è 9,19 % per entrambi.

⚠️ La soglia dell'aliquota aggiuntiva è **56.224 €**. Molti calcolatori online riportano ancora
52.190 €, valore di anni precedenti: è uno dei punti in cui i risultati divergono.

```
base_contributiva  = min(RAL, 122.295)
contributi_base    = base_contributiva × aliquota_settore
eccedenza          = max(0, base_contributiva − 56.224)
contributi_aggiunt = eccedenza × 1%
contributi_totali  = contributi_base + contributi_aggiunt
```

Il massimale si applica **prima** dell'aliquota aggiuntiva: oltre 122.295 € di RAL i contributi
non crescono più.

---

## 2. IRPEF

**Fonte:** L. 199/2025 (Legge di Bilancio 2026), art. 1 co. 3, che ha portato la seconda
aliquota dal 35 % al 33 % con effetto dal 01/01/2026.

| Scaglione di imponibile | Aliquota |
| --- | --- |
| fino a 28.000 € | 23 % |
| da 28.000 a 50.000 € | 33 % |
| oltre 50.000 € | 43 % |

Progressiva **a scaglioni**: ogni aliquota si applica solo alla propria fetta di reddito.
Guadagnare un euro in più non abbassa mai il netto per effetto degli scaglioni.

L'imponibile fiscale è `RAL − contributi_totali`, non la RAL.

### Aliquota marginale

È l'aliquota dello scaglione più alto raggiunto dall'imponibile. Serve per il calcolo delle
mensilità aggiuntive (§ 7).

---

## 3. Detrazione per lavoro dipendente

**Fonte:** art. 13 TUIR, nella formulazione vigente per il 2026.

| Imponibile | Detrazione |
| --- | --- |
| fino a 15.000 € | 1.955 € fissi |
| da 15.000 a 28.000 € | 1.910 + 1.190 × (28.000 − imponibile) / 13.000 |
| da 28.000 a 50.000 € | 1.910 × (50.000 − imponibile) / 22.000 |
| oltre 50.000 € | 0 |

Due raccordi lineari distinti, non uno solo. Il primo parte da 1.910 e ci somma una quota
variabile che si annulla a 28.000; il secondo scende da 1.910 a zero fra 28.000 e 50.000.

L'importo fisso è passato da 1.880 a **1.955 €**. Quel cambio è la ragione dell'offset di 75 €
descritto al § 5: senza, l'aumento avrebbe escluso i redditi più bassi dal trattamento
integrativo.

---

## 4. Taglio del cuneo fiscale

**Fonte:** taglio del cuneo fiscale, confermato per il 2026.

Sotto un solo nome convivono **due strumenti diversi e mutuamente esclusivi**. La distinzione
non è formale: cambia dove il beneficio agisce.

### 4a. Sotto i 20.000 € di imponibile — somma esente

Non è una detrazione: è una somma **erogata in busta e non tassata**. Non tocca l'IRPEF, si
aggiunge al netto. La percentuale si sceglie in base alla fascia e si applica poi **all'intero
imponibile**, non per scaglioni.

| Imponibile | Percentuale |
| --- | --- |
| fino a 8.500 € | 7,1 % |
| da 8.500 a 15.000 € | 5,3 % |
| da 15.000 a 20.000 € | 4,8 % |

Il salto di percentuale sull'intero importo è il motivo dei gradini a 8.500 e 15.000 (§ 8).

### 4b. Sopra i 20.000 € di imponibile — ulteriore detrazione

Detrazione ordinaria, abbatte l'IRPEF lorda.

| Imponibile | Detrazione |
| --- | --- |
| da 20.000 a 32.000 € | 1.000 € |
| da 32.000 a 40.000 € | 1.000 × (40.000 − imponibile) / 8.000 |
| oltre 40.000 € | 0 |

I due rami non si sommano mai: sotto 20.000 esiste solo la somma esente, sopra solo la
detrazione.

---

## 5. Trattamento integrativo (ex "bonus Renzi")

**Fonte:** art. 1 D.L. 3/2020, come modificato da **L. 207/2024, art. 1 co. 3**.

Somma esente erogata in busta, come la 4a: non abbatte l'imposta, si aggiunge al netto.

| Imponibile | Spettanza |
| --- | --- |
| fino a 15.000 € | 1.200 € **se** IRPEF lorda > (detrazione lavoro − 75) |
| da 15.000 a 28.000 € | min(1.200, max(0, detrazione lavoro − IRPEF lorda)) |
| oltre 28.000 € | 0 |

### ⚠️ L'offset di 75 € — il dettaglio che sbagliano quasi tutti

Il test di capienza sotto i 15.000 confronta l'IRPEF lorda con la detrazione da lavoro
**diminuita di 75 €**, non con la detrazione piena.

È la correzione che compensa l'aumento della detrazione da 1.880 a 1.955: senza l'offset,
l'innalzamento avrebbe spinto fuori dal beneficio proprio i redditi che il beneficio doveva
proteggere. Nella pratica: la soglia effettiva del test è **1.880 €** (cioè 1.955 − 75).

La fascia in cui l'offset decide l'esito è quella in cui l'IRPEF lorda sta **fra 1.880 e 1.955**,
cioè imponibili intorno agli **8.200 – 8.500 €**. Lì il calcolo corretto concede 1.200 € e il
calcolo ingenuo li nega. Su una RAL di circa 9.100 € la differenza è l'intero trattamento.

### La seconda regola che quasi tutti sbagliano

Nella fascia 15.000 – 28.000 il "capiente" si misura **solo** sulle detrazioni elencate
all'art. 1 co. 1-bis del D.L. 3/2020: artt. **12, 13 e 15 co. 1 lett. a) e b) TUIR**.

**L'ulteriore detrazione del taglio del cuneo non è in quell'elenco.** Includerla nel confronto
inventerebbe capienza che al trattamento non spetta. In questo modello semplificato (nessun
familiare a carico, nessun interesse passivo su mutuo) l'unica detrazione che conta è quella
da lavoro dipendente, e il risultato è che nella fascia 15.000 – 28.000 il trattamento è
**sempre zero**.

---

## 6. Addizionali locali

### Regionale — Lombardia

**Fonte:** Regione Lombardia, addizionale regionale IRPEF (aliquote invariate dal 2022).

| Scaglione di imponibile | Aliquota |
| --- | --- |
| fino a 15.000 € | 1,23 % |
| da 15.000 a 28.000 € | 1,58 % |
| da 28.000 a 50.000 € | 1,72 % |
| oltre 50.000 € | 1,73 % |

Progressiva **a scaglioni**, come l'IRPEF.

### Comunale — Milano

**Fonte:** Comune di Milano, **delibera n. 46 del 28/09/2020**, tuttora in vigore e
riconfermata il 20/12/2025.

Verificata sull'**elenco generale MEF delle aliquote comunali**, che riporta testualmente
*«Esenzione per redditi imponibili fino a euro 23.000,00»* più un'aliquota unica dello 0,8%.
L'elenco è scaricabile in CSV, un file per anno d'imposta:

```
https://www1.finanze.gov.it/finanze2/dipartimentopolitichefiscali/fiscalitalocale/addirpef_newDF/download/download.php?anno=2026
```

⚠️ Il file dell'anno corrente è **parziale**: i comuni pubblicano le delibere durante l'anno.
Ad agosto 2026, 4.809 comuni su 7.897 non hanno ancora una delibera 2026 e riportano `0*`,
Milano compresa. Per quelli vale l'ultima delibera pubblicata — da cui la citazione al 2025.

| Parametro | Valore |
| --- | --- |
| Aliquota | 0,8 % |
| Soglia di esenzione | 23.000 € di imponibile |

**È un'esenzione, non una franchigia.** Fino a 23.000 € di imponibile non si paga nulla; un euro
sopra la soglia, lo 0,8 % si applica **all'intero imponibile**, non alla sola eccedenza. Da qui
il terzo gradino (§ 8).

I calcolatori online che trattano questa soglia come franchigia sbagliano, e molti riportano
ancora 21.000 € invece di 23.000.

---

## 7. Dalla RAL al netto — ordine delle operazioni

L'ordine non è arbitrario: è quello in cui le regole si applicano in busta paga. I contributi
vengono per primi perché **riducono la base imponibile** di tutto ciò che segue.

```
1.  contributi        = vedi § 1
2.  imponibile        = RAL − contributi
3.  IRPEF lorda       = scaglioni progressivi § 2 sull'imponibile
4.  detrazioni        = § 3 (lavoro) + § 4b (cuneo, se imponibile > 20.000)
5.  IRPEF netta       = max(0, IRPEF lorda − detrazioni)
6.  addizionali       = § 6, entrambe sull'imponibile
7.  somme esenti      = § 4a (bonus cuneo) + § 5 (trattamento integrativo)

    trattenute totali = contributi + IRPEF netta + addizionali
    NETTO ANNUO       = RAL − trattenute totali + somme esenti
```

L'IRPEF netta non è mai negativa: se le detrazioni superano l'imposta lorda, l'eccedenza si
perde (non genera credito).

### Perché le somme esenti non sono una fetta della RAL

Il bonus del cuneo e il trattamento integrativo **non escono dal lordo: ci si aggiungono**. Il
netto annuo può quindi superare quello che si otterrebbe sottraendo alla RAL tutte le
trattenute. Qualsiasi grafico che divide la RAL in fette deve tenerle **fuori** dalle fette e
mostrarle a parte, altrimenti le proporzioni sommano più del 100 %.

---

## 8. Le mensilità

La RAL si divide per il numero di mensilità (12, 13 o 14): il lordo annuo **non cambia**, viene
distribuito su più buste.

```
lordo_per_busta     = RAL / numero_mensilità
aliquota_contributi = contributi_totali / RAL
contributi_busta    = lordo_per_busta × aliquota_contributi
imponibile_busta    = lordo_per_busta − contributi_busta
```

### Le mensilità aggiuntive rendono meno di una ordinaria

A parità di lordo, la tredicesima e la quattordicesima lasciano in mano **meno** di un mese
ordinario. Due ragioni, entrambe reali:

1. Le detrazioni sono già consumate dalle dodici buste ordinarie, quindi sulle extra si
   trattiene all'**aliquota marginale** piena.
2. Le addizionali locali si trattengono sulle ordinarie, non sulle extra.

```
n_extra              = numero_mensilità − 12
imposta_extra_lorda  = imponibile_busta × aliquota_marginale × n_extra
imposta_extra_totale = min(imposta_extra_lorda, IRPEF_netta_annua)
imposta_per_extra    = imposta_extra_totale / n_extra        (0 se n_extra = 0)

busta_extra:
    lordo        = lordo_per_busta
    contributi   = contributi_busta
    IRPEF        = imposta_per_extra
    addizionali  = 0
    esenti       = 0
    netto        = lordo − contributi − IRPEF

busta_ordinaria:
    lordo        = lordo_per_busta
    contributi   = contributi_busta
    IRPEF        = (IRPEF_netta_annua − imposta_extra_totale) / 12
    addizionali  = addizionali_totali / 12
    esenti       = somme_esenti / 12
    netto        = (NETTO_ANNUO − somma_netti_extra) / 12
```

Il `min` al terzo rigo non è difensivo: con redditi bassi le detrazioni azzerano l'imposta
annua, e senza il cap si tratterrebbe sulle extra più di quanto è dovuto per l'anno intero.

**I totali annui sono la verità, le buste si derivano da quelli.** Mai il contrario: così le
buste risommano sempre al netto annuo esatto.

La quota esente va mostrata come riga della busta ordinaria. Senza, il netto di quella busta
non torna con le sue stesse voci, e chi legge cedolini se ne accorge.

---

## 9. I tre gradini

In tre punti la curva del netto **scende** al crescere del lordo. Non sono bug: sono soglie di
legge in cui un beneficio viene ricalcolato sull'intero reddito o perso del tutto.

| Soglia (imponibile) | Cosa succede |
| --- | --- |
| **8.500 €** | La somma esente del cuneo passa dal 7,1 % al 5,3 % dell'intero imponibile |
| **15.000 €** | Si perde il trattamento integrativo (1.200 €) e la somma esente scende al 4,8 % |
| **23.000 €** | Finisce l'esenzione dell'addizionale comunale di Milano: lo 0,8 % colpisce tutto |

Le soglie sono sull'**imponibile**, non sulla RAL. In termini di RAL cadono rispettivamente
intorno a 9.400 €, 16.550 € e 25.325 €.

Il motore deve superare un test che percorre tutto l'arco dei redditi a passi di 100 € e
pretende che **ogni** calo del netto sia spiegato dall'attraversamento di una di queste tre
soglie. Un calo altrove è un errore di implementazione.

Ogni gradino deve inoltre essere **recuperato** entro poche centinaia di euro di lordo: un
gradino da cui il netto non risale sarebbe un errore di modello, non una regola.

---

## 10. Caso di riferimento

**RAL 35.000 €, commercio, 14 mensilità.** Calcolato a mano dai parametri 2026.

| Passo | Calcolo | Risultato |
| --- | --- | --- |
| Contributi | 35.000 × 9,19 % | 3.216,50 |
| Imponibile | 35.000 − 3.216,50 | 31.783,50 |
| IRPEF lorda | 28.000 × 23 % + 3.783,50 × 33 % | 7.688,56 |
| Detrazione lavoro | 1.910 × (50.000 − 31.783,50) / 22.000 | 1.581,52 |
| Ulteriore detrazione | forfait, il reddito sta sotto i 32.000 | 1.000,00 |
| IRPEF netta | 7.688,56 − 1.581,52 − 1.000 | 5.107,03 |
| Add. regionale | 15.000 × 1,23 % + 13.000 × 1,58 % + 3.783,50 × 1,72 % | 454,98 |
| Add. comunale | 31.783,50 × 0,8 %, soglia superata | 254,27 |
| **Netto annuo** | 35.000 − 3.216,50 − 5.107,03 − 454,98 − 254,27 | **25.967,22** |

Nessuna somma esente: l'imponibile è sopra 20.000 e sopra 28.000.

Questo caso è stato verificato con un'implementazione indipendente delle norme su 29 RAL
diverse: **coincidono al centesimo**.

---

## 11. Casi di verifica

Da portare integralmente nella suite Pest. Tolleranza: 2 decimali.

### Contributi

| Caso | Atteso |
| --- | --- |
| 35.000, commercio | totale 3.216,50 · aggiuntiva 0 |
| 35.000, industria vs commercio | differenza = 35.000 × 0,30 % |
| 66.224, commercio | aggiuntiva = 100,00 · totale = 66.224 × 9,19 % + 100 |
| 200.000 vs 122.295 | totali identici · base contributiva = 122.295 |

### IRPEF e detrazioni (argomento = imponibile)

| Caso | Atteso |
| --- | --- |
| 60.000 | lorda = 28.000×23 % + 22.000×33 % + 10.000×43 % |
| 51.000 | detrazione lavoro = 0 |
| 31.783,50 | ulteriore detrazione = 1.000 |
| 36.000 | ulteriore detrazione = 500 |
| 40.000 | ulteriore detrazione = 0 |
| 18.000 | bonus esente = 18.000 × 4,8 % · ulteriore detrazione = 0 |
| 14.000 | trattamento integrativo = 1.200 (lorda 3.220 supera la soglia) |
| 8.000 | trattamento integrativo = 0 (lorda 1.840 sotto 1.880) |
| **8.300** | **trattamento = 1.200** — lorda fra 1.880 e 1.955: è il caso che prova l'offset |
| 16.000 · 21.000 · 25.000 · 27.900 | trattamento = 0 in tutta la fascia |
| 8.000 | IRPEF netta = 0, mai negativa |
| marginale su 20.000 / 31.783,50 / 80.000 | 23 % / 33 % / 43 % |

### Addizionali (argomento = imponibile)

| Caso | Atteso |
| --- | --- |
| 31.783,50 | regionale = 15.000×1,23 % + 13.000×1,58 % + 3.783,50×1,72 % |
| 23.000 | comunale = 0 |
| 23.001 | comunale = 23.001 × 0,8 % — sull'intero, non sull'eccedenza |

### Buste (RAL 35.000, commercio)

- Con 14 mensilità: 2 buste extra, ognuna con lordo pari all'ordinaria e **netto inferiore**.
- Per 12, 13 e 14: `netto_ordinaria × 12 + Σ netti_extra` = netto annuo.
- Con 12 mensilità: nessuna busta extra.
- Per RAL 16.000 e 35.000: ogni busta deve riconciliare
  `lordo − contributi − IRPEF − addizionali + esenti = netto`.
  (16.000 ha una somma esente: una busta che la ignora mostra un netto inspiegabile.)
- Per RAL 16.000: l'imposta trattenuta sulle extra non supera mai l'IRPEF netta annua, e
  l'IRPEF della busta ordinaria non è mai negativa.

### Coerenza sull'arco dei redditi

Su 12.000 · 18.000 · 25.000 · 35.000 · 55.000 · 80.000 · 130.000:

- netto sempre < lordo e sempre > 0
- `netto + trattenute − somme esenti = RAL`
- il netto cresce sempre (questi valori stanno lontani dai tre gradini)
- a parità di RAL, l'industria lascia meno netto del commercio

### I gradini

- **Milano:** RAL 25.320 vs 25.330 — la prima sotto 23.000 di imponibile, la seconda sopra,
  netto in calo.
- **15.000:** RAL 16.500 vs 16.600 — trattamento da 1.200 a 0, netto in calo.
- **Monotonia:** da 1.000 a 300.000 € di RAL a passi di 100, ogni calo del netto deve
  corrispondere all'attraversamento di 8.500, 15.000 o 23.000 di imponibile.
- **Recupero:** da RAL 9.400, 16.600 e 25.400, il netto a +3.000 € di lordo è superiore.

---

## 12. Assunzioni del modello

Dichiarate, non nascoste. Sono le semplificazioni ammesse dalla traccia.

- Impiegato a tempo indeterminato, anno lavorato per intero
- Residenza a Milano, Lombardia
- Nessun familiare a carico
- Nessuna agevolazione (impatriati, rientro cervelli, premi di risultato)
- Nessun fondo pensione né welfare aziendale
- Nessun interesse passivo su mutuo né altro onere detraibile
- Non si calcola il costo azienda: solo la quota a carico del dipendente
- Il TFR non è considerato

## 13. Limiti noti

- L'aliquota dell'addizionale comunale di Milano e la soglia di 23.000 € sono confermate
  sull'ultima delibera disponibile; un'eventuale delibera 2026 pubblicata dopo la stesura non
  è recepita.
- Le percentuali del taglio del cuneo per il 2026 sono quelle risultanti dalla normativa
  vigente alla stesura.
- I contributi sono calcolati sulla RAL annua e non ricostruiti mese per mese: differenze da
  conguaglio infrannuale non sono modellate.
- Il modello non gestisce redditi diversi da quello di lavoro dipendente.

**Non sostituisce il conteggio di un consulente del lavoro.**
