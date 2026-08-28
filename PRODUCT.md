# Product

## Register

product

## Users

Due lettori, con bisogni opposti.

Il **valutatore di Jet HR** apre la pagina da laptop, in ufficio, e ha meno di un minuto: vuole
capire se i numeri tornano e se chi ha scritto il codice ha capito il dominio. Legge buste paga
di mestiere, quindi nota subito una voce mancante o un totale che non quadra.

Il **dipendente curioso** conosce solo la cifra sul contratto e non sa perché in banca ne arriva
molta meno. Non deve imparare il gergo fiscale per usare lo strumento: deve poterlo attraversare
un passo alla volta e uscirne avendo capito qualcosa.

## Product Purpose

Trasformare una RAL nel netto annuo e nelle singole buste, mostrando ogni trattenuta lungo il
percorso. Ha successo se un utente non esperto arriva in fondo sapendo dire dove sono finiti i
soldi, e se un esperto non trova nulla di sbagliato.

## Brand Personality

Preciso, trasparente, paziente. Il tono è quello di un consulente che spiega senza far pesare
di saperne di più: dice cosa succede e perché, non vende nulla e non semplifica fino a mentire.
Tre parole: **rigoroso, leggibile, onesto**.

## Anti-references

- I calcolatori di stipendio esistenti: muri di banner pubblicitari attorno a una tabella grigia,
  costanti fiscali di tre anni fa, nessuna indicazione di come sia stato ottenuto il risultato.
- L'estetica landing-page SaaS: numero enorme al centro, gradiente, tre card identiche sotto.
  Qui il numero finale conta meno del percorso che ci porta.
- Il cruscotto fintech navy-e-oro. Questo non è un prodotto finanziario, è un foglio di calcolo
  che sa spiegarsi.

## Design Principles

1. **Il percorso è il prodotto.** Il netto è una riga; le trattenute che lo producono sono il
   contenuto. Nessuna schermata deve mostrare un totale senza il suo scomposto.
2. **Un passo alla volta.** L'utente non esperto si perde davanti a sei campi insieme. Il wizard
   esiste per ridurre il carico, non per fare scena.
3. **Ogni numero è difendibile.** Se una cifra compare a schermo, la sua fonte è nel repository.
   Le semplificazioni sono dichiarate, non nascoste.
4. **Le stranezze del sistema si mostrano, non si lisciano.** La soglia di esenzione di Milano
   produce un gradino nel netto. È corretto: va spiegato, non arrotondato via.
5. **Familiarità guadagnata.** Controlli standard, comportamenti prevedibili. Lo strumento deve
   sparire dentro il compito.

## Accessibility & Inclusion

Obiettivo WCAG 2.2 AA. Contrasto minimo 4.5:1 sul testo normale e 3:1 su quello grande, verificato
e non stimato. Il wizard è interamente navigabile da tastiera con focus visibile su ogni controllo.
Il colore non è mai l'unico veicolo di significato: trattenute e accrediti portano sempre anche un
segno (− / +) e un'etichetta testuale, perché la distinzione rosso/verde è la più comune forma di
daltonismo. `prefers-reduced-motion` sostituisce ogni transizione con un cambio di stato immediato.
Le cifre sono in tabular numerals per restare allineate fra un passo e l'altro.
