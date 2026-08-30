<template>
  <tr class="row" :class="{ 'row--total': sign === 'equals' }">
    <th scope="row" class="row__label">
      <span class="row__sign" aria-hidden="true">{{ signSymbol(sign) }}</span>
      <span class="row__sr">{{ signLabel(sign) }}</span>
      <span>
        {{ label }}
        <small v-if="note" class="row__note">{{ note }}</small>
      </span>
    </th>
    <td class="row__amount tabular">{{ formatEuro(amount) }}</td>
  </tr>
</template>

<script setup lang="ts">
import { useCurrency, type LineSign } from '@/composables/useCurrency';

/**
 * One line of the gross-to-net journey.
 *
 * The operator is a real character in the markup and a real word for screen readers, not a
 * colour: docs/PRODOTTO.md requires that money leaving and money arriving stay distinguishable
 * without colour vision, and red/green is the most common way that fails.
 */
defineProps<{
  label: string;
  amount: number;
  sign: LineSign;
  note?: string | undefined;
}>();

const { formatEuro, signLabel, signSymbol } = useCurrency();
</script>

<style scoped lang="scss">
.row {
  border-bottom: 1px solid var(--hairline);
}

.row--total {
  border-bottom: none;
  font-weight: 700;

  .row__amount {
    color: var(--amber);
    font-size: 1.125rem;
  }
}

.row__label {
  text-align: left;
  font-weight: inherit;
  padding: 0.6rem 0;
  display: flex;
  gap: 0.6rem;
  align-items: baseline;
}

.row__sign {
  color: var(--azure);
  font-weight: 700;
  width: 1ch;
  flex: none;
}

/* Visible to assistive technology only: the operator as a word. */
.row__sr {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}

.row__note {
  display: block;
  color: var(--muted);
  font-size: 0.8125rem;
  font-weight: 400;
  line-height: 1.35;
}

.row__amount {
  text-align: right;
  padding: 0.6rem 0;
  white-space: nowrap;
}
</style>
