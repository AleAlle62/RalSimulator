# Prodotto

Il perché delle scelte di interfaccia. Le scelte tecniche stanno in [../CLAUDE.md](../CLAUDE.md),
quelle fiscali in [FISCO-2026.md](FISCO-2026.md).

## Due lettori, con bisogni opposti

Il **valutatore di Jet HR** apre la pagina da laptop, in ufficio, e ha meno di un minuto: vuole
capire se i numeri tornano e se chi ha scritto il codice ha capito il dominio. Legge buste paga
di mestiere, quindi nota subito una voce mancante o un totale che non quadra.

Il **dipendente curioso** conosce solo la cifra sul contratto e non sa perché in banca ne arriva
molta meno. Non deve imparare il gergo fiscale per usare lo strumento: deve poterlo attraversare
un passo alla volta e uscirne avendo capito qualcosa.

Ogni schermata serve entrambi, mai uno a scapito dell'altro. Il dettaglio tecnico c'è sempre, ma
sta dietro una progressive disclosure: chi verifica lo apre, chi impara non ci inciampa.

## Scopo

Trasformare una RAL nel netto annuo e nelle singole buste, mostrando ogni trattenuta lungo il
percorso — e permettere di conservare e condividere il risultato.

Ha successo se un utente non esperto arriva in fondo sapendo dire dove sono finiti i soldi, e se
un esperto non trova nulla di sbagliato.

## Tono

Preciso, trasparente, paziente. Quello di un consulente che spiega senza far pesare di saperne di
più: dice cosa succede e perché, non vende nulla e non semplifica fino a mentire.

Tre parole: **rigoroso, leggibile, onesto**.

## Anti-riferimenti

- **I calcolatori di stipendio esistenti**: muri di banner attorno a una tabella grigia, costanti
  fiscali di tre anni fa, nessuna indicazione di come sia stato ottenuto il risultato.
- **L'estetica landing-page SaaS**: numero enorme al centro, gradiente, tre card identiche sotto.
  Qui il numero finale conta meno del percorso che ci porta. **Vale da dentro il calcolatore in
  poi** — wizard, risultato, riga per riga: lì niente decorazione, il contenuto è il numero e la
  sua scomposizione. La landing è esplicitamente esclusa, vedi sotto.
- **Il cruscotto fintech navy-e-oro.** Questo non è un prodotto finanziario: è un foglio di
  calcolo che sa spiegarsi.

## Principi

1. **Il percorso è il prodotto.** Il netto è una riga; le trattenute che lo producono sono il
   contenuto. Nessuna schermata mostra un totale senza il suo scomposto.
2. **Un passo alla volta.** L'utente non esperto si perde davanti a sei campi insieme. Il wizard
   esiste per ridurre il carico, non per fare scena.
3. **Ogni numero è difendibile.** Se una cifra compare a schermo, la sua fonte è raggiungibile —
   e nel database, accanto alla costante. Le semplificazioni sono dichiarate, non nascoste.
4. **Le stranezze del sistema si mostrano, non si lisciano.** La soglia di esenzione di Milano
   produce un gradino nel netto. È corretto: va spiegato, non arrotondato via.
5. **Familiarità guadagnata.** Controlli standard, comportamenti prevedibili. Lo strumento deve
   sparire dentro il compito.

La landing page è l'unica schermata che può permettersi di essere memorabile invece che
invisibile: è l'unica il cui compito è convincere qualcuno a cominciare.

**Ed è l'unica dove l'anti-riferimento numero due non si applica.** Decisione presa il
30/08/2026, contro quanto diceva prima questo stesso documento: la landing usa uno sfondo
animato (Aurora di [Vue Bits](https://vue-bits.dev), WebGL, MIT) e un gradiente. Il motivo è che
i due anti-riferimenti colpiscono cose diverse — il problema del cruscotto SaaS non è il
gradiente in sé, è **il gradiente al posto del contenuto**: numero enorme, tre card vuote,
niente da leggere. Qui il contenuto c'è e sta tutto sopra la piega; la decorazione fa il lavoro
che le compete, cioè far venire voglia di premere il pulsante.

Un vincolo che la tiene onesta:

- **Non scrolla.** Una schermata sola, come da elenco delle schermate. Se la spiegazione non ci
  sta, si taglia la spiegazione, non si aggiunge scroll.

`prefers-reduced-motion` disattiva l'animazione dello sfondo, come ogni altra transizione.

### Il tema non si ferma più alla CTA

Decisione presa il **30/08/2026**, contro quanto diceva prima questo stesso documento. Il vincolo
era: *«si ferma alla CTA — nessuno sfondo animato dietro dei numeri che qualcuno deve leggere e
verificare»*. Ora Silk e il vetro corrono anche dietro wizard, risultato e simulazioni salvate.

Quel vincolo era scritto per proteggere una cosa sola, la **leggibilità delle cifre**, e la
proteggeva con lo strumento sbagliato: vietava il trattamento invece di vincolare il contrasto. Il
problema non è mai stato lo sfondo, è la traslucenza sotto una colonna di importi. Quindi il
divieto diventa una soglia:

- I pannelli che portano cifre usano `.glass-panel`, tinta al **93%** di `--ink`. A quel punto il
  testo poggia su un fondo praticamente opaco e ciò che resta del vetro è il bordo e la
  rifrazione attorno.
- **Misurato sul caso peggiore che può davvero capitare**, non stimato: pixel più chiaro del
  canvas Silk campionato dal canvas (`rgb(42, 74, 134)`), velo al suo punto più trasparente, e
  nessuno sconto per la sfocatura, che può solo abbassare quel picco. Corpo **16,57:1**,
  secondario **7,67:1**, azzurro **8,94:1**, ambra **11,48:1**.

L'anti-riferimento numero due resta in piedi per quello che dice davvero: **niente decorazione al
posto del contenuto**. Nel risultato la decorazione sta dietro, il contenuto è ancora il numero e
la sua scomposizione, e nessun totale compare senza le righe che lo producono.

## Accessibilità

Obiettivo **WCAG 2.2 AA**.

- Contrasto minimo 4.5:1 sul testo normale e 3:1 su quello grande, verificato e non stimato.
- Wizard interamente navigabile da tastiera, con focus visibile su ogni controllo.
- Il colore non è mai l'unico veicolo di significato: trattenute e accrediti portano sempre anche
  un segno (`−` / `+`) e un'etichetta testuale, perché la distinzione rosso/verde è la forma più
  comune di daltonismo.
- Il donut non fa eccezione: ogni fetta ha la sua voce in legenda, con etichetta e importo.
- `prefers-reduced-motion` sostituisce ogni transizione con un cambio di stato immediato — loader
  del calcolo compreso.
- Cifre in tabular numerals, così le colonne restano allineate fra un passo e l'altro.
