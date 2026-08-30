<template>
  <tr class="line" :class="{ 'line--total': sign === 'equals' }">
    <th scope="row" class="line__label">
      <span class="line__inner">
        <span class="line__sign" aria-hidden="true">{{ signSymbol(sign) }}</span>
        <span class="line__sr">{{ signLabel(sign) }}</span>
        <span>
          {{ label }}
          <small v-if="note" class="line__note">{{ note }}</small>
        </span>
      </span>
    </th>
    <td class="line__amount tabular">{{ formatEuro(amount) }}</td>
  </tr>
</template>

<script setup lang="ts">
import { useCurrency, type LineSign } from '@/composables/useCurrency';

/**
 * One line of the gross-to-net journey.
 *
 * The operator is a real character in the markup and a real word for screen readers, not a
 * colour: the README requires that money leaving and money arriving stay distinguishable
 * without colour vision, and red/green is the most common way that fails.
 *
 * The block is called `line` and not `row` because `.row` is Quasar's flexbox grid utility. Named
 * `row`, the <tr> inherited `display: flex` from it, which drops the whole table out of the table
 * formatting context: every line then sized its own label and amount, and the column of euro
 * amounts came out ragged instead of aligned.
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
.line {
  border-bottom: 1px solid var(--hairline);
}

.line--total {
  border-bottom: none;
  font-weight: 700;

  .line__amount {
    color: var(--amber);
    font-size: 1.125rem;
  }
}

/**
 * The cell stays a real table cell and the flex layout lives one level in: `display: flex` on a
 * <th> takes it out of the table formatting context the same way it does on a <tr>.
 *
 * `width: 100%` is what pins the figures to the right edge — it makes the amount cell shrink to
 * its own content whatever the label next to it says.
 */
.line__label {
  text-align: left;
  font-weight: inherit;
  padding: 0.6rem 0;
  width: 100%;
}

.line__inner {
  display: flex;
  gap: 0.6rem;
  align-items: baseline;
}

.line__sign {
  color: var(--azure);
  font-weight: 700;
  width: 1ch;
  flex: none;
}

/* Visible to assistive technology only: the operator as a word. */
.line__sr {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
}

.line__note {
  display: block;
  color: var(--muted);
  font-size: 0.8125rem;
  font-weight: 400;
  line-height: 1.35;
}

.line__amount {
  text-align: right;
  padding: 0.6rem 0;
  white-space: nowrap;
}
</style>
