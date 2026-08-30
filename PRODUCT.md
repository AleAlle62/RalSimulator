# RalSimulator

**This file is a pointer, not a source.** The product decisions live in
[docs/PRODOTTO.md](docs/PRODOTTO.md) and were written before it — in Italian, and in more detail
than this summary. Where the two disagree, PRODOTTO.md wins and this file is the one to fix.

It exists because tooling looks for a file named `PRODUCT.md`; duplicating the real document
into English would leave the project with two specs drifting apart, which is the failure mode
[CLAUDE.md](CLAUDE.md) is written to avoid.

Related: [CLAUDE.md](CLAUDE.md) for architecture, [docs/FISCO-2026.md](docs/FISCO-2026.md) for
the tax rules, [docs/STATO.md](docs/STATO.md) for what is built and what is next.

## Register

**Split, and deliberately so.**

- **Brand** — the landing page only. One screen, no scroll. Its job is to make someone start,
  so it is allowed an animated WebGL backdrop and a gradient. It is the single surface where
  the "SaaS aesthetic" anti-reference is suspended, and PRODOTTO.md records why.
- **Product** — everything from the wizard onward: input steps, result, line-by-line breakdown,
  saved simulations, and the Filament admin panel. Design serves the numbers here. No animated
  backdrops behind figures somebody has to read and verify.

Default for any task not obviously the landing: **product**.

## Users

Two readers with opposite needs, both served by every screen:

- **The Jet HR reviewer** — laptop, in an office, under a minute. Reads payslips for a living,
  so a missing line or a total that does not add up is noticed immediately. Wants to know if
  the figures hold and if the author understood the domain.
- **The curious employee** — knows only the number on their contract and not why less arrives
  in the bank. Must not have to learn tax jargon to use the tool, and should leave able to say
  where the money went.

Technical detail is always present but behind progressive disclosure: the reviewer opens it,
the learner does not trip over it.

## Purpose

Turn a gross annual salary (RAL) into the net figure and the individual payslips, showing every
withholding along the way, with each number traceable to its legal source. Save and share the
result.

Success: a non-expert finishes knowing where the money went, and an expert finds nothing wrong.

## Personality

**Rigoroso, leggibile, onesto** — rigorous, legible, honest. The tone of a consultant who
explains without making you feel they know more: says what happens and why, sells nothing, and
never simplifies to the point of lying.

## Anti-references

1. **Existing salary calculators** — walls of banners around a gray table, tax constants three
   years stale, no indication of how the result was reached.
2. **The SaaS landing aesthetic** — huge number, gradient, three identical cards. Applies from
   the wizard onward; the landing is the documented exception.
3. **The navy-and-gold fintech dashboard** — this is not a financial product. It is a
   spreadsheet that can explain itself.

## Principles

1. **The path is the product.** The net is one line; the withholdings that produce it are the
   content. No screen shows a total without its breakdown.
2. **One step at a time.** The wizard exists to reduce load, not for show.
3. **Every number is defensible.** If a figure is on screen its source is reachable, and stored
   next to the constant in the database.
4. **The system's oddities are shown, not smoothed.** Milan's exemption threshold produces a
   step in the net curve. That is correct: explain it, do not round it away.
5. **Earned familiarity.** Standard controls, predictable behaviour. The tool disappears into
   the task.

## Accessibility

Target **WCAG 2.2 AA**, verified rather than estimated.

- Contrast at least 4.5:1 on body text, 3:1 on large text.
- The wizard is fully keyboard navigable, with visible focus on every control.
- Colour never carries meaning alone: withholdings and credits always also carry a sign
  (`−` / `+`) and a text label, because red/green is the most common colour blindness.
- The donut is no exception: every slice has a legend entry with label and amount.
- `prefers-reduced-motion` replaces every transition with an immediate state change, the
  landing's animated backdrop included.
- Tabular numerals, so columns stay aligned from one step to the next.
