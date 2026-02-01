<template>

  <v-autocomplete
    v-model="model"
    :items="countries"
    label="Paese di provenienza*"
    variant="outlined"
    class="mb-3"
    :disabled="disabled"
  >
    <template #item="{ props, item }">
      <v-list-item v-bind="props">
        <template #prepend>
          <span :class="`fi fi-${item.raw.value} mr-2`" />
        </template>
      </v-list-item>
    </template>

    <template #selection="{ item }">
      <span :class="`fi fi-${item.raw.value} mr-2`" />
      {{ item.raw.title }}
    </template>
  </v-autocomplete>

</template>

<script setup>

import CountryList from 'country-list-with-dial-code-and-flag'

const countries = CountryList.getAll().map(country => ({
  title: country.name,
  value: country.code.toLowerCase(),
}))

const model = defineModel();

</script>