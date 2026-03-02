<template>
  <v-autocomplete
    v-model="model"
    :items="countries"
    :label="label"
    variant="outlined"
    class="mb-3"
    :disabled="disabled"
    item-title="title"
    item-value="value"
    :custom-filter="searchFilter"
    :error-messages="errorMessages"
    auto-select-first
  >
    <template #item="{ props, item }">
      <v-list-item v-bind="props" :title="item.raw.title">
        <template #prepend>
          <span :class="`fi fi-${item.raw.flag} mr-3`" style="font-size: 1.2rem;" />
        </template>
      </v-list-item>
    </template>

    <template #selection="{ item }">
      <span :class="`fi fi-${item.raw.flag} mr-2`" style="font-size: 1rem;" />
      {{ item.raw.title }}
    </template>
  </v-autocomplete>
</template>

<script setup>
import CountryList from 'country-list-with-dial-code-and-flag'

defineProps({
  label:         { type: String, default: 'Paese*' },
  disabled:      { type: Boolean, default: false },
  errorMessages: { type: [String, Array], default: undefined },
})

const model = defineModel()

// Deduplica per codice e separa flag (lowercase) da value (uppercase)
const countries = [
  ...new Map(
    CountryList.getAll().map(c => [
      c.code.toUpperCase(),
      {
        title: c.name,
        value: c.code.toUpperCase(), // salvato uppercase per il backend
        flag:  c.code.toLowerCase(), // usato solo per la classe CSS
      }
    ])
  ).values()
].sort((a, b) => a.title.localeCompare(b.title))

// Ricerca su nome e codice
function searchFilter(_, queryText, item) {
  const q = queryText.toLowerCase()
  return (
    item.raw.title.toLowerCase().includes(q) ||
    item.raw.value.toLowerCase().includes(q)
  )
}
</script>