# Componenti di terze parti

File copiati da librerie esterne, non scritti per questo progetto. Vanno tenuti invariati:
se serve un comportamento diverso, si adatta il consumo lato applicazione.

| File          | Origine                                                            | Licenza |
| ------------- | ------------------------------------------------------------------ | ------- |
| `CountUp.vue` | [vue-bits](https://vue-bits.dev/) — `TextAnimations/CountUp`        | MIT     |

## Divergenze dall'upstream

**1. Type-check.** La callback dell'`IntersectionObserver` destrutturava direttamente il primo
elemento (`([entry]) => ...`). Con `noUncheckedIndexedAccess` attivo il type-check lo segnala,
perché l'array potrebbe essere vuoto. È stato riscritto in `entries[0]` con optional chaining:
comportamento identico, nessun errore di compilazione.

**2. Avvio dell'animazione.** Il componente fa partire il conteggio solo quando
l'`IntersectionObserver` segnala che l'elemento è entrato nel viewport. In alcuni contesti di
rendering quella callback non viene mai invocata, e la cifra resta bloccata sul valore iniziale:
per il netto annuo significherebbe mostrare `0`. È stata aggiunta `startIfAlreadyVisible()`, che
al mount verifica la geometria dell'elemento e avvia subito l'animazione se è già a schermo.
L'observer resta per gli elementi sotto la piega.

## Nota su `CountUp.vue`

Il componente anima il numero una sola volta, quando entra nel viewport: cambiare la prop
`to` non fa ripartire l'animazione. Per questo `SalaryWaterfall.vue` gli passa una `key`
legata al contatore `revision`, così a ogni calcolo il componente viene rimontato e la cifra
riparte da zero. La logica del componente resta intatta.
