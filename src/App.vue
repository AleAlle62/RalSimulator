<template>
  <div class="min-h-screen">
    <div class="mx-auto flex max-w-[44rem] flex-col px-5 py-8 sm:px-8 sm:py-12">
      <header class="flex items-start justify-between gap-4 pb-10">
        <div class="flex flex-col gap-1">
          <h1 class="text-2xl font-semibold tracking-[-0.02em] text-ink">Dal lordo al netto</h1>
          <p class="text-sm text-ink-muted">
            Anno d’imposta {{ TAX_YEAR_2026.year }} · impiegato a Milano
          </p>
        </div>
        <ThemeToggle />
      </header>

      <main>
        <SalaryWizard />
      </main>

      <footer class="mt-16 border-t border-border pt-6">
        <p class="text-xs text-ink-muted">
          Prototipo open source. Ogni aliquota usata nel calcolo è documentata, con la sua fonte,
          in
          <code class="rounded-sm bg-surface px-1 py-0.5">src/domain/taxYear2026.ts</code>.
        </p>
      </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
import SalaryWizard from './components/wizard/SalaryWizard.vue'
import ThemeToggle from './components/ThemeToggle.vue'
import { TAX_YEAR_2026 } from '@/domain'
</script>
