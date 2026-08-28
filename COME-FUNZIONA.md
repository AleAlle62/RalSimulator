# Come si passa dal lordo al netto

Questa è la spiegazione per chi non sa nulla di buste paga. Nessuna formula prima di averla
raccontata a parole, e un unico esempio che seguiamo dall'inizio alla fine.

**L'esempio**: Giulia, impiegata a Milano, contratto del commercio, **RAL di 35.000 €**,
stipendio diviso in 14 mensilità.

RAL vuol dire *Retribuzione Annua Lorda*: la cifra scritta sul contratto. Non è quello che
arriva in banca. Quello che arriva in banca è **25.967 €**. Questo documento spiega dove sono
finiti gli altri 9.033 €.

---

## L'idea in una frase

Dal lordo vengono tolte tre cose, in quest'ordine: **i contributi** (la pensione futura),
**l'IRPEF** (l'imposta sul reddito) e **le addizionali** (le tasse di regione e comune).
L'ordine conta, perché ogni passaggio cambia la base su cui si calcola il successivo.

```
35.000 €  RAL
 −3.217   contributi INPS
 −5.107   IRPEF
   −455   addizionale regionale
   −254   addizionale comunale
────────
25.967 €  netto in un anno
```

---

## Tappa 1 — I contributi INPS

**Cosa sono.** Una quota che finanzia la tua pensione futura. Non è una tassa: sono soldi
accantonati a tuo nome. Li versa in parte l'azienda e in parte tu; qui contiamo solo la tua parte,
perché è l'unica che ti viene tolta dalla busta.

**Quanto.** Il **9,19%** del lordo se lavori nel commercio, il **9,49%** nell'industria. La
differenza sono trenta centesimi ogni cento euro, il contributo CIGS, che le aziende industriali
versano e le piccole aziende commerciali no.

Giulia sta nel commercio: `35.000 × 9,19% = 3.216,50 €`.

**Due dettagli che scattano solo sugli stipendi alti.** Sopra i **56.224 €** l'aliquota sale di
un punto sulla parte eccedente. E sopra i **122.295 €** i contributi si fermano: è un tetto, oltre
quello non si versa più nulla. Giulia è sotto entrambe le soglie, quindi non la riguardano.

**Perché questa tappa viene per prima.** Perché i contributi si sottraggono *prima* di calcolare
le tasse. Sulla quota che va alla pensione non paghi l'IRPEF.

Quello che resta si chiama **imponibile fiscale**, ed è la base di tutto il resto:

```
35.000 − 3.216,50 = 31.783,50 €
```

---

## Tappa 2 — L'IRPEF

È l'imposta sul reddito, la voce più pesante. Si calcola in due tempi: prima quanto dovresti,
poi quanto ti viene scontato.

### 2a. L'imposta lorda: a scaglioni, non a fascia unica

Questo è il punto che quasi tutti fraintendono. L'aliquota **non si applica a tutto il reddito**:
il reddito viene tagliato a fette, e ogni fetta paga la sua percentuale.

| Fetta di reddito      | Aliquota |
| --------------------- | -------- |
| da 0 a 28.000 €       | 23%      |
| da 28.000 a 50.000 €  | 33%      |
| oltre 50.000 €        | 43%      |

Giulia ha 31.783,50 € di imponibile, quindi paga il 23% sui primi 28.000 e il 33% solo sui
3.783,50 che avanzano:

```
28.000,00 × 23% = 6.440,00
 3.783,50 × 33% = 1.248,56
                 ─────────
                  7.688,56 €  imposta lorda
```

> **Il malinteso da evitare.** "Se supero i 28.000 pago il 33% su tutto e ci rimetto" è falso.
> Guadagnare un euro in più non fa mai scendere il netto: quell'euro in più paga di più, ma solo
> quell'euro.

Nota: nel 2026 la seconda aliquota è scesa **dal 35% al 33%**. Un calcolatore che usa ancora il
35% sbaglia tutto l'intervallo fra 28.000 e 50.000 €.

### 2b. Le detrazioni: gli sconti

Sull'imposta lorda si applicano degli sconti. Sono tre, e dipendono da quanto guadagni.

**La detrazione da lavoro dipendente.** Spetta a chiunque sia dipendente. Vale 1.955 € per i
redditi bassi, poi cala man mano che il reddito sale, e sopra i 50.000 € sparisce.

Giulia è nella fascia 28.000–50.000, dove lo sconto si riduce in proporzione a quanto manca ai
50.000:

```
1.910 × (50.000 − 31.783,50) / 22.000 = 1.581,52 €
```

**Il taglio del cuneo fiscale.** È lo stesso provvedimento che funziona in due modi diversi:

- **sotto i 20.000 €**: non è uno sconto sulle tasse, è una *somma esente* che ti viene aggiunta
  in busta e su cui non paghi nulla (7,1%, 5,3% o 4,8% del reddito, a seconda della fascia);
- **fra 20.000 e 40.000 €**: è uno sconto vero e proprio, 1.000 € fino a 32.000, che poi cala
  fino ad azzerarsi a 40.000.

Giulia è a 31.783,50: prende i **1.000 €** pieni.

**Il trattamento integrativo.** Il vecchio "bonus Renzi": 1.200 € l'anno, ma solo sotto i
15.000 € di reddito e solo se l'imposta è abbastanza alta da assorbirlo. Giulia guadagna troppo,
quindi non le spetta.

### 2c. L'imposta netta

```
7.688,56 − 1.581,52 − 1.000,00 = 5.107,03 €
```

Se gli sconti superassero l'imposta, il risultato non diventa negativo: si ferma a zero.

---

## Tappa 3 — Le addizionali

Regione e comune applicano una loro piccola imposta sullo stesso imponibile fiscale.

**Regionale (Lombardia).** A scaglioni, come l'IRPEF: 1,23% fino a 15.000, 1,58% da 15.000 a
28.000, 1,72% da 28.000 a 50.000, 1,73% oltre.

```
15.000,00 × 1,23% = 184,50
13.000,00 × 1,58% = 205,40
 3.783,50 × 1,72% =  65,08
                   ────────
                    454,98 €
```

**Comunale (Milano).** Aliquota dello 0,80%, ma chi ha un imponibile **fino a 23.000 € non paga
nulla**.

```
31.783,50 × 0,80% = 254,27 €
```

Attenzione a come funziona questa esenzione: non è una franchigia. Se superi 23.000 anche di un
euro, lo 0,80% si applica su **tutto** il reddito, non solo sulla parte eccedente. Ne riparliamo
più sotto, perché produce un effetto che sembra un errore.

---

## Il risultato

```
35.000,00   lordo
−3.216,50   contributi
−5.107,03   IRPEF
  −454,98   addizionale regionale
  −254,27   addizionale comunale
──────────
25.967,22 € netto in un anno
```

Su 100 € di lordo, Giulia ne porta a casa circa 74.

---

## Perché la tredicesima è più bassa

Giulia ha 14 mensilità: il lordo annuo viene diviso in 14 buste da 2.500 € l'una. Stesso lordo,
ma il netto no:

| Busta                | Lordo    | Netto        |
| -------------------- | -------- | ------------ |
| Mensilità ordinaria  | 2.500 €  | **1.910,42 €** |
| Tredicesima          | 2.500 €  | **1.521,07 €** |
| Quattordicesima      | 2.500 €  | **1.521,07 €** |

Quasi 390 € di differenza a parità di lordo. Il motivo:

1. **Gli sconti sono già stati usati.** Le detrazioni sono spalmate sulle dodici mensilità
   ordinarie. Quando arriva la tredicesima non ne resta niente da applicare, e l'IRPEF si paga
   piena, all'aliquota più alta che hai raggiunto (per Giulia il 33%).
2. **Le addizionali non si toccano.** Vengono trattenute sulle buste ordinarie, non sulle extra.

Non è una penalizzazione: il totale annuo è identico. È solo che il conto viene diviso in modo
disuguale, e in busta la differenza si vede.

Molti calcolatori online mostrano una "media mensile" dividendo il netto annuo per le mensilità.
È un numero che non corrisponde a nessuna busta reale. Qui abbiamo scelto di mostrare le cifre
vere.

---

## Due risultati che sembrano sbagliati e non lo sono

**1. A 12.000 € di RAL il netto è quasi uguale al lordo.**
Vengono trattenuti circa 1.788 € fra contributi e tasse, ma rientrano 577 € di somma esente e
1.200 € di trattamento integrativo. Il saldo è quasi in pari. È il sistema che è fatto così sui
redditi bassi.

**2. Un euro in più può far scendere il netto.**
Succede una volta sola, sulla soglia dei 23.000 € di imponibile a Milano. Sotto quella cifra
l'addizionale comunale non si paga; sopra, si paga sull'intero reddito. Chi supera la soglia di
un euro si ritrova con circa 184 € in meno all'anno. È un difetto reale della norma, non del
calcolo: lo mostriamo invece di arrotondarlo via.

---

## Cosa questo simulatore non calcola

Il caso coperto è quello standard, e tutto il resto è dichiarato:

- Impiegato a tempo indeterminato, per l'anno intero. Niente part-time, apprendistato o assunzioni
  a metà anno.
- Residenza a Milano. Cambiando comune o regione cambiano le addizionali.
- Nessun familiare a carico, che darebbe diritto ad altre detrazioni.
- Nessuna agevolazione: né regime impatriati, né premi di risultato, né fringe benefit.
- Nessuna adesione a un fondo pensione, che ridurrebbe l'imponibile.
- Le addizionali sono imputate all'anno di competenza. Nella realtà si versano l'anno successivo,
  in acconto e saldo.
- Il TFR non compare: matura a parte e non entra nella busta mensile.

Per il dettaglio delle fonti normative di ogni singola aliquota, vedi il
[README](README.md) e il file [`src/domain/taxYear2026.ts`](src/domain/taxYear2026.ts).
