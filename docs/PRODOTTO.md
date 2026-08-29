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
  Qui il numero finale conta meno del percorso che ci porta.
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
invisibile: è l'unica il cui compito è convincere qualcuno a cominciare. Ma vale comunque
l'anti-riferimento numero due.

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
