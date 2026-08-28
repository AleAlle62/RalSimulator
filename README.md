# Simulatore RAL → netto

Calcolatore che parte da una Retribuzione Annua Lorda e restituisce il netto annuo, il dettaglio
di ogni trattenuta e il valore reale di ogni busta paga, tredicesima e quattordicesima comprese.

Anno d'imposta **2026**. Caso coperto: impiegato a tempo indeterminato, residente a Milano,
senza agevolazioni.

> **Non sai nulla di buste paga?** Parti da [COME-FUNZIONA.md](COME-FUNZIONA.md): spiega tutta la
> catena di calcolo in italiano semplice, seguendo un unico esempio dall'inizio alla fine.

---

## Avvio

```bash
npm install && npm run dev
```

| Comando              | Cosa fa                                  |
| -------------------- | ---------------------------------------- |
| `npm run dev`        | Server di sviluppo                       |
| `npm run test:unit`  | 29 test sul motore di calcolo            |
| `npm run type-check` | Controllo dei tipi                       |
| `npm run build`      | Build di produzione                      |

---

## Com'è organizzato

La logica fiscale è **TypeScript puro, senza alcuna dipendenza da Vue**: si può testare senza
montare un componente, e si potrebbe riusare in un backend senza toccarla.

```
src/
├── domain/                    logica fiscale, framework-agnostic
│   ├── taxYear2026.ts         ogni aliquota e soglia, con la fonte accanto
│   ├── brackets.ts            applicazione degli scaglioni
│   ├── socialContributions.ts contributi INPS
│   ├── incomeTax.ts           IRPEF lorda e netta
│   ├── taxReliefs.ts          detrazioni, cuneo fiscale, trattamento integrativo
│   ├── localSurtaxes.ts       addizionali regionale e comunale
│   ├── payslipSchedule.ts     ripartizione sulle buste reali
│   └── salaryCalculator.ts    orchestratore della catena
├── composables/               stato reattivo (calcolo, wizard, tema)
├── presentation/              formattazione ed etichette in italiano
└── components/                UI a step
```

Il criterio: `taxYear2026.ts` contiene **solo dati**, il resto contiene **solo regole**.
Aggiornare il simulatore al 2027 è una modifica a un file di configurazione, non al codice. La
funzione di calcolo riceve l'anno d'imposta come parametro, quindi due anni diversi possono
convivere.

---

## Fonti

Ogni costante usata nel calcolo è riportata qui con la sua fonte. Le stesse citazioni sono nei
commenti di [`src/domain/taxYear2026.ts`](src/domain/taxYear2026.ts), accanto al valore.

| Voce                                    | Valore 2026                | Fonte |
| --------------------------------------- | -------------------------- | ----- |
| Aliquota INPS c/dipendente, commercio    | 9,19%                      | [Tabelle aliquote contributive INPS](https://www.kitech.it/Contributi-previdenziali.aspx?p=4_138) |
| Aliquota INPS c/dipendente, industria    | 9,49% (9,19% + 0,30% CIGS) | [Tabelle aliquote contributive INPS](https://www.kitech.it/Contributi-previdenziali.aspx?p=6_157) |
| Soglia aliquota aggiuntiva 1%            | 56.224 €                   | [INPS, Circolare n. 6 del 30/01/2026](https://www.inps.it/it/it/inps-comunica/atti/circolari-messaggi-e-normativa/dettaglio.circolari-e-messaggi.2026.01.circolare-numero-6-del-30-01-2026_15151.html) |
| Massimale contributivo annuo             | 122.295 €                  | [INPS, Circolare n. 6 del 30/01/2026](https://www.inps.it/it/it/inps-comunica/atti/circolari-messaggi-e-normativa/dettaglio.circolari-e-messaggi.2026.01.circolare-numero-6-del-30-01-2026_15151.html) |
| Scaglioni IRPEF                          | 23% / 33% / 43%            | [Legge di Bilancio 2026 (L. 199/2025, art. 1 co. 3)](https://fiscomania.com/aliquote-irpef/) |
| Detrazione da lavoro dipendente          | art. 13 TUIR               | [Detrazioni lavoro dipendente 2026](https://www.informazionefiscale.it/detrazioni-lavoro-dipendente-importo-calcolo) |
| Taglio del cuneo fiscale                 | esente ≤20k, 1.000 € 20–40k | [Bonus cuneo fiscale 2026](https://www.fiscoetasse.com/new-rassegna-stampa/3664-cu-2026-il-bonus-per-il-taglio-del-cuneo-fiscale.html) |
| Trattamento integrativo                  | 1.200 € ≤ 15.000 €         | [Trattamento integrativo 2026](https://leggeinchiaro.it/trattamento-integrativo-detrazioni-lavoro-dipendente/) |
| Addizionale regionale Lombardia          | 1,23% → 1,73% a scaglioni  | [Regione Lombardia](https://www.regione.lombardia.it/bollo-auto-e-tributi-regionali/red-addizionale-regionale-irpef) |
| Addizionale comunale Milano              | 0,80%, esenzione a 23.000 €| [Addizionale comunale Milano 2026](https://www.tuttocalcolo.it/addizionale-irpef/lombardia/milano) |

### Due valori su cui la rete è sbagliata

Vale la pena segnalarli, perché sono il tipo di errore che si propaga da un calcolatore all'altro.

1. **Prima fascia pensionabile.** Diverse fonti secondarie riportano ancora **52.190 €**, che è un
   valore di anni precedenti. Il dato 2026 della circolare INPS è **56.224 €**.
2. **Soglia di esenzione comunale a Milano.** Si trova spesso **21.000 €**: era corretto fino al
   2020, oggi è **23.000 €**.

### Affidabilità delle fonti

Non tutte hanno lo stesso peso e vale la pena dirlo:

- Le soglie INPS vengono dalla circolare ufficiale, ma la pagina è servita via JavaScript e non è
  stato possibile leggerne il PDF in modo automatico: i valori provengono dalla sintesi
  istituzionale. **Da riconfermare sul PDF prima di un uso non dimostrativo.**
- Le aliquote per settore vengono da tabelle di settore, affidabili ma non ufficiali.
- Il Comune di Milano non ha pubblicato una delibera per il 2026: si applicano i valori vigenti.

I risultati sono stati verificati con calcoli manuali passo per passo (il caso di riferimento è
documentato nei test). **Non** sono stati confrontati con un simulatore commerciale: è un
controllo incrociato che vale la pena fare prima di dare i numeri per definitivi.

---

## Scelte e semplificazioni

Tutte deliberate, tutte discutibili in sede di colloquio.

**Sul perimetro**

- Impiegato a tempo indeterminato, anno intero. Niente part-time, apprendistato, assunzioni
  infrannuali.
- Nessun familiare a carico, nessuna agevolazione (impatriati, premi di risultato, fringe
  benefit), nessun fondo pensione.
- Due soli settori, commercio e industria, perché sono i due che cambiano davvero l'aliquota a
  carico del dipendente.

**Sul metodo**

- Le addizionali sono imputate all'anno di competenza. Nella realtà si versano l'anno successivo
  fra acconto e saldo: modellare lo sfasamento avrebbe complicato il codice senza cambiare il
  totale annuo.
- Le buste extra sono calcolate all'aliquota marginale senza detrazioni, che è ciò che fa il
  cedolino reale, con un vincolo: il prelievo sulle extra non può mai superare l'imposta
  effettivamente dovuta per l'anno.
- La busta ordinaria è ricavata per differenza dal netto annuo. Così la somma delle buste
  coincide sempre col totale annuo, al centesimo. È verificato da un test.
- Il TFR non è modellato: matura a parte e non entra nella busta.
- Il costo azienda non è calcolato: la traccia chiede il punto di vista del dipendente.

---

## Test

29 test su [`src/domain/__tests__`](src/domain/__tests__), divisi in due gruppi.

**Sulle singole voci**: aliquote per settore, scatto dell'1% oltre la prima fascia, tetto
contributivo, scaglioni IRPEF, ciascuna detrazione nella sua fascia, soglia di Milano.

**Sull'insieme**: un caso di riferimento a 35.000 € calcolato a mano e riportato passo per passo
in cima al file, più alcune proprietà che devono valere su tutto l'arco dei redditi:

- la somma delle buste coincide col netto annuo, al centesimo;
- lordo, trattenute, somme esenti e netto quadrano sempre;
- il netto cresce ogni volta che cresce il lordo;
- le buste extra sono sempre più basse di una ordinaria;
- il prelievo sulle extra non supera mai l'imposta annua dovuta.

Il gradino sulla soglia di Milano ha un test dedicato: è un comportamento voluto, non una
regressione.

---

## Stack

Vue 3 · TypeScript · Vite · Tailwind CSS v4 · Vitest

Font di sistema, nessuna richiesta di rete per il testo. Tema chiaro e scuro, scelta persistita e
applicata prima del primo paint. I componenti animati `Counter` e `CountUp` vengono da
[vue-bits](https://vue-bits.dev/); le modifiche rispetto all'originale sono documentate in
[`src/components/vendor/README.md`](src/components/vendor/README.md).

Il risultato è costruito attorno a una domanda sola, "dove sono finiti i miei soldi": una barra
impilata divide il lordo in quattro parti con importo e percentuale, e il dettaglio contabile
resta fuori. La prima versione mostrava un registro a righe con il saldo progressivo dopo ogni
trattenuta: corretto, ma leggibile solo da chi sa già leggere un cedolino.

Contrasti verificati in entrambi i temi: tutto il testo supera WCAG AA, con un minimo misurato di
6,09:1 dove la soglia è 3:1.

---

## Avvertenza

Prototipo dimostrativo. Copre il caso standard di un impiegato e non sostituisce il conteggio di
un consulente del lavoro.
