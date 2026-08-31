{{--
  La pagina di una simulazione condivisa, renderizzata dal server.

  Non duplica il risultato interattivo: quello vive nella SPA, su /risultato/{token}, e il
  pulsante qui sotto ci porta. Questa pagina esiste perché i crawler non eseguono JavaScript,
  quindi di una SPA leggono soltanto l'<head> statico — identico per ogni simulazione. È qui che
  il netto di *questa* simulazione entra nell'anteprima del link.

  Lo stile è in linea, e volutamente: la pagina deve funzionare anche se la SPA non è stata
  compilata, quindi non può dipendere dai suoi asset. I colori sono gli stessi token di
  src/css/app.scss, tenuti allineati a mano perché sono cinque.
--}}
@php
    $result = $simulation->result;
    $net = $result['netAnnualSalary'] ?? 0;
    $monthlyNet = $result['payslips']['ordinary']['net'] ?? 0;
    $gross = $simulation->gross_annual_salary;
    $sector = $simulation->sector->value === 'industry' ? 'industria' : 'commercio';
    $city = $simulation->municipality?->name ?? '—';
    $euro = fn (float $n) => number_format($n, 2, ',', '.') . ' €';

    // La RAL è quasi sempre una cifra tonda: "35.000 €" si legge meglio di "35.000,00 €".
    $euroTidy = fn (float $n) => fmod($n, 1.0) === 0.0
        ? number_format($n, 0, ',', '.') . ' €'
        : $euro($n);

    // Apostrofo tipografico, non quello dritto: Blade lo escaperebbe in &#039; dentro il
    // content dei meta, e un unfurler pigro lo mostrerebbe così com'è nell'anteprima.
    $title = $euro($net) . ' netti su ' . $euroTidy($gross) . ' di RAL';
    $description = sprintf(
        'Busta ordinaria da %s, %d mensilità, %s, %s. Anno d’imposta %s: contributi, IRPEF e addizionali voce per voce.',
        $euro($monthlyNet),
        $simulation->monthly_payments_count,
        $sector,
        $city,
        $result['year'] ?? '2026',
    );
@endphp
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — RAL Simulator</title>
    <meta name="description" content="{{ $description }}">

    {{--
      Le simulazioni sono pubbliche solo per chi ha il token, che non è indovinabile. Non c'è
      motivo per cui debbano finire in un motore di ricerca: sono cifre di stipendio di qualcuno.
      Il noindex non tocca l'anteprima dei link — Slack, WhatsApp e LinkedIn scaricano la pagina
      e leggono i meta comunque, perché non stanno indicizzando.
    --}}
    <meta name="robots" content="noindex, nofollow">

    <meta property="og:type" content="article">
    <meta property="og:site_name" content="RAL Simulator">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url('/s/' . $simulation->token) }}">
    <meta property="og:locale" content="it_IT">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">

    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('/icons/favicon-32x32.png') }}">
    <link rel="icon" href="{{ url('/favicon.ico') }}">

    <style>
        :root {
            --ink: #080c14;
            --surface-raised: #182132;
            --bone: #e8eef7;
            --muted: #96a4bc;
            --azure: #7fb2ff;
            --edge: rgba(232, 238, 247, 0.18);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 1.5rem;
            background: var(--ink);
            color: var(--bone);
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            line-height: 1.5;
        }

        .card {
            width: min(34rem, 100%);
            padding: clamp(1.5rem, 5vw, 2.5rem);
            border: 1px solid var(--edge);
            border-radius: 16px;
            background:
                linear-gradient(158deg, rgba(232, 238, 247, 0.08) 0%, rgba(232, 238, 247, 0) 62%),
                var(--surface-raised);
            box-shadow: inset 0 1px 0 rgba(232, 238, 247, 0.2), 0 18px 40px -24px #000;
        }

        .eyebrow {
            margin: 0;
            font-size: 0.8125rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .net {
            margin: 0.5rem 0 0;
            font-size: clamp(2.25rem, 9vw, 3.25rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            font-variant-numeric: tabular-nums;
        }

        .net span { display: block; font-size: 0.9375rem; font-weight: 400; color: var(--muted); }

        .rows { margin: 1.75rem 0 0; border-top: 1px solid var(--edge); }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            padding: 0.625rem 0;
            border-bottom: 1px solid var(--edge);
            font-size: 0.9375rem;
        }

        .row dt { margin: 0; color: var(--muted); }
        .row dd { margin: 0; font-variant-numeric: tabular-nums; }

        .cta {
            display: inline-block;
            margin-top: 1.75rem;
            padding: 0.7rem 1.2rem;
            border: 1px solid var(--azure);
            border-radius: 999px;
            color: var(--azure);
            text-decoration: none;
            font-weight: 600;
        }

        .cta:focus-visible { outline: 2px solid var(--azure); outline-offset: 3px; }

        .note { margin: 1.5rem 0 0; font-size: 0.8125rem; color: var(--muted); }
    </style>
</head>
<body>
    <main class="card">
        <p class="eyebrow">Simulazione condivisa</p>

        <p class="net">
            {{ $euro($net) }}
            <span>netti all’anno, su {{ $euroTidy($gross) }} di RAL</span>
        </p>

        <dl class="rows">
            <div class="row">
                <dt>Busta ordinaria</dt>
                <dd>{{ $euro($monthlyNet) }}</dd>
            </div>
            <div class="row">
                <dt>Mensilità</dt>
                <dd>{{ $simulation->monthly_payments_count }}</dd>
            </div>
            <div class="row">
                <dt>Settore</dt>
                <dd>{{ ucfirst($sector) }}</dd>
            </div>
            <div class="row">
                <dt>Comune</dt>
                <dd>{{ $city }}</dd>
            </div>
            <div class="row">
                <dt>Anno d’imposta</dt>
                <dd>{{ $result['year'] ?? '2026' }}</dd>
            </div>
        </dl>

        <a class="cta" href="{{ url('/risultato/' . $simulation->token) }}">
            Apri la simulazione completa
        </a>

        <p class="note">
            Le cifre sono quelle calcolate al momento della simulazione e non vengono ricalcolate:
            questo link mostrerà sempre gli stessi numeri. Non sostituisce il conteggio di un
            consulente del lavoro.
        </p>
    </main>
</body>
</html>
