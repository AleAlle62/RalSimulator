# Design

## Theme

Ufficio paghe milanese di tardo pomeriggio: acciaio degli schedari, blu della carta carbone, la
calma di un conto chiuso. Freddo, istituzionale, preciso, senza essere ostile.

Strategia di colore: **Restrained**. Neutri con una punta di freddo, un solo accento teal per le
azioni primarie e lo stato corrente, e due colori semantici che esistono solo per distinguere il
denaro che esce da quello che resta. Nessun colore decorativo.

Il chiaro è il default: la pagina si legge in un ufficio illuminato. Lo scuro è disponibile e
persistito, non un ripiego.

## Color

Tutti i valori in OKLCH. Il fondo chiaro è bianco puro: l'identità la portano l'accento e la
tipografia, non la superficie.

### Light

| Ruolo         | Valore                    | Uso                                    |
| ------------- | ------------------------- | -------------------------------------- |
| `bg`          | `oklch(1 0 0)`            | Fondo pagina                           |
| `surface`     | `oklch(0.978 0.003 220)`  | Pannelli, celle, barra di avanzamento  |
| `border`      | `oklch(0.906 0.005 220)`  | Bordi e righe                          |
| `ink`         | `oklch(0.216 0.012 240)`  | Testo primario                         |
| `ink-muted`   | `oklch(0.486 0.011 240)`  | Testo secondario (4.5:1 su `bg`)       |
| `primary`     | `oklch(0.45 0.074 200)`   | Azioni, step corrente, focus           |
| `withheld`    | `oklch(0.505 0.155 25)`   | Trattenute                             |
| `kept`        | `oklch(0.47 0.11 155)`    | Netto e accrediti                      |

### Dark

| Ruolo         | Valore                    |
| ------------- | ------------------------- |
| `bg`          | `oklch(0.174 0.012 240)`  |
| `surface`     | `oklch(0.223 0.013 240)`  |
| `border`      | `oklch(0.303 0.014 240)`  |
| `ink`         | `oklch(0.955 0.004 240)`  |
| `ink-muted`   | `oklch(0.735 0.011 240)`  |
| `primary`     | `oklch(0.76 0.09 200)`    |
| `withheld`    | `oklch(0.72 0.14 25)`     |
| `kept`        | `oklch(0.76 0.13 155)`    |

Rosso e verde non portano mai da soli il significato: ogni importo ha anche il segno e l'etichetta.

## Typography

Una sola famiglia: lo stack di sistema (`ui-sans-serif, system-ui, ...`), che rende SF su Apple,
Segoe su Windows, Roboto su Android. È la scelta corretta per un tool: familiare, già installata,
zero richieste di rete.

Scala fissa in rem, non fluida: l'utente sta dentro un compito, non guarda una landing.
Rapporto ~1.2 fra i passi.

| Ruolo         | Dimensione | Peso | Note                             |
| ------------- | ---------- | ---- | -------------------------------- |
| Titolo pagina | 1.5rem     | 600  | `-0.02em`                        |
| Titolo step   | 1.25rem    | 600  |                                  |
| Corpo         | 0.9375rem  | 400  |                                  |
| Etichetta     | 0.8125rem  | 500  |                                  |
| Micro         | 0.75rem    | 400  | Note e didascalie                |
| Cifre         | variabile  | 500  | `tabular-nums`, `-0.01em`        |

Le cifre usano tabular numerals così le colonne restano allineate fra uno step e l'altro.

## Layout

Colonna singola centrata, `max-width: 44rem`. Un wizard non ha bisogno di una griglia: ha bisogno
che l'occhio sappia sempre dove ricominciare a leggere.

Tre passi, perché sono davvero una sequenza: la numerazione porta informazione, non decorazione.

1. **Quanto guadagni** — la RAL
2. **Come sei inquadrato** — mensilità e settore
3. **Il risultato** — netto, scomposto e buste

Raggio: 8px sui controlli, 12px sui pannelli. Mai oltre.

## Components

Vocabolario unico su tutta la superficie: stessi controlli, stessi stati (default, hover, focus,
active, disabled), stesso comportamento.

- **Barra di avanzamento**: tre segmenti, quello corrente in `primary`, i completati cliccabili
  per tornare indietro.
- **Barra di ripartizione**: una sola barra impilata che mostra come il lordo si divide in quattro
  parti, con la legenda sotto. Sostituisce il registro a righe della prima versione, che mostrava
  il saldo progressivo: era la vista di un contabile, non la risposta alla domanda "dove sono
  finiti i miei soldi". Ogni voce porta importo **e** percentuale, così la proporzione si coglie
  senza leggere le cifre.
- **Griglia buste**: ordinaria, tredicesima, quattordicesima affiancate. Solo netto e lordo: lo
  scomposto è già stato dato dalla barra di ripartizione.
- **Assunzioni**: dentro un `<details>` chiuso. Servono a chi verifica, non a chi calcola.

I quattro colori della ripartizione codificano un significato, non decorano: verde e teal sono
soldi tuoi (subito o da pensionato), i due rossi sono soldi che escono. Il colore non è mai
l'unico veicolo: accanto c'è sempre l'etichetta.

### Componenti vue-bits

- **Counter**: il netto annuo, con le cifre che scorrono a rullo. Molto visibile e a tema con
  l'oggetto della pagina.
- **CountUp**: disponibile ma non più in uso dopo che Counter ha preso il suo posto.

Scartati di proposito: **Stepper** porta colori scuri cablati (`#222`, `bg-zinc-600`) che
romperebbero il tema chiaro, e **AnimatedContent** richiede GSAP e nasconde il contenuto finché
l'animazione non parte.

## Motion

150–250 ms, `ease-out`. La transizione fra i passi è un crossfade con un leggero scorrimento
orizzontale che segue la direzione della navigazione. I segmenti della barra di ripartizione
crescono in sequenza quando il risultato compare.

`prefers-reduced-motion: reduce` sostituisce tutto con cambi di stato immediati. Il contenuto è
visibile di default: nessuna animazione fa da interruttore alla visibilità.
