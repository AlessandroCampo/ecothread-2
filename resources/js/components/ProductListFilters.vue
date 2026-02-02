<template>
  <v-card variant="flat" class="mb-3">
    <v-card-text class="pa-3">
      <v-text-field
        v-model="localFilters.search"
        density="compact"
        variant="outlined"
        placeholder="Cerca per nome o ID..."
        prepend-inner-icon="mdi-magnify"
        clearable
        hide-details
        class="mb-3"
        @update:model-value="debouncedEmit"
      />

      <v-row dense>
        <v-col cols="6">
          <v-select
            v-model="localFilters.status"
            :items="statusOptions"
            density="compact"
            variant="outlined"
            label="Stato"
            clearable
            hide-details
            @update:model-value="emitFilters"
          />
        </v-col>

        <v-col cols="6">
          <v-select
            v-model="localFilters.product_type"
            :items="productTypes"
            item-title="label"
            item-value="value"
            density="compact"
            variant="outlined"
            label="Tipo"
            clearable
            hide-details
            @update:model-value="emitFilters"
          />
        </v-col>
      </v-row>
    </v-card-text>
  </v-card>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({})
  },
  productTypes: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update:filters'])

const localFilters = ref({ ...props.filters })

const statusOptions = [
  { title: 'On-chain', value: 'on-chain' },
  { title: 'Pending', value: 'pending' }
]

let debounceTimer = null
const debouncedEmit = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    emitFilters()
  }, 300)
}

const emitFilters = () => {
  emit('update:filters', { ...localFilters.value })
}

watch(() => props.filters, (newVal) => {
  localFilters.value = { ...newVal }
}, { deep: true })
</script>
