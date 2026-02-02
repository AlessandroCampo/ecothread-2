<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      Prodotti
      <v-chip size="small" class="ml-2">{{ totalProducts }}</v-chip>
      <v-spacer />
      <v-btn color="primary" size="small">
        <v-icon left>mdi-plus</v-icon> Nuovo
        <v-dialog activator="parent" max-width="min(600px, 90vw)" persistent>
          <template #default="{ isActive }">
            <ProductForm
              @cancel="isActive.value = false"
              @created="(p) => onProductCreated(p, isActive)"
              :productTypes
            />
          </template>
        </v-dialog>
      </v-btn>
    </v-card-title>

    <ProductListFilters
      :filters="localFilters"
      :product-types="productTypes"
      @update:filters="onFiltersChange"
    />

    <v-list v-if="productsList.length">
      <ProductListItem
        v-for="product in productsList"
        :key="product.id"
        :product="product"
        :is-selected="selectedProductId === product.id"
        @select="$emit('select', $event)"
        @edit="openEditModal"
        @add-event="openEventModal"
        @request-passport="onRequestPassport"
        @show-requirements="openRequirementsDialog"
        @view-passport="onViewPassport"
      />
    </v-list>

    <v-card-text v-else class="text-center text-grey">
      <template v-if="hasActiveFilters">
        <v-icon size="48" class="mb-2">mdi-filter-off</v-icon>
        <p>Nessun prodotto trovato con i filtri selezionati</p>
        <v-btn variant="text" size="small" @click="clearFilters">
          Rimuovi filtri
        </v-btn>
      </template>
      <template v-else>
        <v-icon size="48" class="mb-2">mdi-package-variant</v-icon>
        <p>Nessun prodotto</p>
      </template>
    </v-card-text>

    <v-card-actions v-if="products.last_page > 1" class="justify-center">
      <v-pagination
        :model-value="products.current_page"
        :length="products.last_page"
        :total-visible="5"
        density="compact"
        @update:model-value="onPageChange"
      />
    </v-card-actions>

    <!-- Modal Modifica -->
    <v-dialog v-model="showEditModal" max-width="600" persistent>
      <ProductForm
        v-if="productToEdit"
        :product="productToEdit"
        :product-types="productTypes"
        @cancel="showEditModal = false"
        @updated="onProductUpdated"
      />
    </v-dialog>

    <!-- Modal Evento -->
    <v-dialog v-model="showEventModal" max-width="600" persistent>
      <EventForm
        v-if="productForEvent"
        :product-id="productForEvent.id"
        :next-index="productForEvent.events?.length || 0"
        :initial-event-type="initialEventType"
        @cancel="showEventModal = false"
        @created="onEventCreated"
      />
    </v-dialog>

    <!-- Dialog Requisiti -->
    <v-dialog v-model="showRequirementsDialog" max-width="450">
      <RequirementsDialog
        v-if="productForRequirements"
        :product="productForRequirements"
        @close="showRequirementsDialog = false"
        @add-event="openEventModalWithType"
        @request-passport="onRequestPassportFromDialog"
      />
    </v-dialog>
  </v-card>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'
import ProductForm from './ProductForm.vue'
import EventForm from './EventForm.vue'
import ProductListFilters from './ProductListFilters.vue'
import ProductListItem from './ProductListItem.vue'
import RequirementsDialog from './RequirementsDialog.vue'

const props = defineProps({
  products: {
    type: Object,
    required: true
  },
  productTypes: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  selectedProductId: {
    type: String,
    default: null
  }
})

const emit = defineEmits(['select', 'update', 'request-passport'])

// State locale
const localFilters = ref({ ...props.filters })
const showEditModal = ref(false)
const showEventModal = ref(false)
const showRequirementsDialog = ref(false)
const productToEdit = ref(null)
const productForEvent = ref(null)
const productForRequirements = ref(null)
const initialEventType = ref(null)

// Computed
const productsList = computed(() => {
  return props.products.data || props.products || []
})

const totalProducts = computed(() => {
  return props.products?.total || productsList.value.length || 0
})

const hasActiveFilters = computed(() => {
  return Object.values(localFilters.value).some(v => v !== null && v !== '' && v !== undefined)
})

// Methods
const onFiltersChange = (newFilters) => {
  localFilters.value = newFilters
  router.get(route('admin.dashboard'), newFilters, {
    preserveState: true,
    preserveScroll: true,
    only: ['products']
  })
}

const onPageChange = (page) => {
  router.get(route('admin.dashboard'), { ...localFilters.value, page }, {
    preserveState: true,
    preserveScroll: true,
    only: ['products']
  })
}

const clearFilters = () => {
  localFilters.value = {}
  onFiltersChange({})
}

const openEditModal = (product) => {
  productToEdit.value = product
  showEditModal.value = true
}

const openEventModal = (product) => {
  initialEventType.value = null
  productForEvent.value = product
  showEventModal.value = true
}

const openEventModalWithType = (eventType) => {
  showRequirementsDialog.value = false
  initialEventType.value = eventType
  productForEvent.value = productForRequirements.value
  showEventModal.value = true
}

const openRequirementsDialog = (product) => {
  productForRequirements.value = product
  showRequirementsDialog.value = true
}

const onRequestPassport = (product) => {
  emit('select', product)
  emit('request-passport', product)
}

const onRequestPassportFromDialog = () => {
  showRequirementsDialog.value = false
  emit('select', productForRequirements.value)
  emit('request-passport', productForRequirements.value)
}

const onViewPassport = (product) => {
  emit('select', product)
}

const onProductCreated = (product, isActive) => {
  isActive.value = false
  router.reload({ only: ['products'] })
}

const onProductUpdated = (product) => {
  showEditModal.value = false
  router.reload({ only: ['products'] })
  emit('update', product)
}

const onEventCreated = (event) => {
  showEventModal.value = false
  router.reload({ only: ['products'] })
}

// Sync filters from props
watch(() => props.filters, (newVal) => {
  localFilters.value = { ...newVal }
}, { deep: true })
</script>
