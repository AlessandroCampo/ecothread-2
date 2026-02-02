<template>
  <v-row>
    <!-- Colonna sinistra: lista prodotti -->
    <v-col cols="12" md="5">
      <ProductList
        :products="products"
        :product-types="productTypes"
        :filters="filters"
        :selected-product-id="selectedProduct?.id"
        @select="selectProduct"
        @update="onProductUpdated"
        @request-passport="scrollToPassportButton"
      />
    </v-col>

    <!-- Colonna destra: dettaglio / form -->
    <v-col cols="12" md="7">
      <!-- Dettaglio prodotto selezionato -->
      <template v-if="selectedProduct">
        <v-card class="mb-4">
          <v-card-title class="d-flex align-center">
            {{ selectedProduct.name }}
            <v-chip
              :color="selectedProduct.is_on_chain ? 'green' : 'orange'"
              size="small"
              class="ml-2"
            >
              {{ selectedProduct.is_on_chain ? 'On-chain' : 'Pending' }}
            </v-chip>
            <v-chip
              v-if="selectedProduct.passport_progress?.has_passport"
              color="success"
              size="small"
              class="ml-2"
            >
              <v-icon size="small" start>mdi-passport</v-icon>
              Passaporto
            </v-chip>
          </v-card-title>
          <v-card-subtitle>{{ selectedProduct.id }}</v-card-subtitle>
          <v-card-text>
            <v-img
              :src="selectedProduct.image_url"
              :alt="selectedProduct.name"
              min-height="200"
              max-height="500"
              cover
              position="top"
            />
            <p v-if="selectedProduct.description" class="mt-3">
              {{ selectedProduct.description }}
            </p>

            <!-- Progress requisiti se non ha passaporto -->
            <v-alert
              v-if="!selectedProduct.passport_progress?.has_passport"
              :type="selectedProduct.passport_progress?.eligible ? 'success' : 'warning'"
              variant="tonal"
              density="compact"
              class="mt-3"
            >
              <div class="d-flex align-center justify-space-between">
                <div>
                  <strong>Requisiti passaporto:</strong>
                  {{ selectedProduct.passport_progress?.count || 0 }}/{{ selectedProduct.passport_progress?.total || 4 }} completati
                </div>
                <v-btn
                  v-if="selectedProduct.passport_progress?.missing?.length"
                  size="x-small"
                  variant="text"
                  @click="showMissingEvents = !showMissingEvents"
                >
                  {{ showMissingEvents ? 'Nascondi' : 'Mostra mancanti' }}
                </v-btn>
              </div>
              <div v-if="showMissingEvents && selectedProduct.passport_progress?.missing?.length" class="mt-2">
                <v-chip
                  v-for="type in selectedProduct.passport_progress.missing"
                  :key="type"
                  size="x-small"
                  color="warning"
                  variant="outlined"
                  class="mr-1 mb-1"
                >
                  {{ eventTypeLabels[type] }}
                </v-chip>
              </div>
            </v-alert>
          </v-card-text>
          <v-card-actions class="d-flex justify-end ga-2 flex-column align-end">
            <SolanaExplorerLink :pda-address="selectedProduct.pda_address" block color="info">
              Vedi on-chain
            </SolanaExplorerLink>
            <v-btn block color="secondary" variant="flat" append-icon="mdi-store-edit">
              MODIFICA {{ selectedProduct?.id }}
              <v-dialog activator="parent" max-width="min(600px, 90vw)" persistent>
                <template #default="{ isActive }">
                  <ProductForm
                    @cancel="isActive.value = false"
                    :product="selectedProduct"
                    :productTypes
                    @updated="(product) => onProductUpdatedFromDetail(product, isActive)"
                  />
                </template>
              </v-dialog>
            </v-btn>
            <v-btn
              color="primary-darken-3"
              append-icon="mdi-plus"
              variant="flat"
              block
              :disabled="!selectedProduct.is_on_chain"
            >
              <v-dialog activator="parent" width="min(600px, 95vw)" :persistent="true">
                <template #default="{ isActive }">
                  <EventForm
                    :product-id="selectedProduct.id"
                    :next-index="selectedProduct.events?.length || 0"
                    @cancel="isActive.value = false"
                    @created="(event) => onEventCreated(event, isActive)"
                  />
                </template>
              </v-dialog>
              Aggiungi Evento
            </v-btn>
            <div ref="passportButtonRef" class="w-100">
              <PassportRequestButton
                :product="selectedProduct"
                @passport-issued="onPassportIssued"
              />
            </div>
          </v-card-actions>
        </v-card>

        <!-- Lista eventi -->
        <v-card v-if="selectedProduct.events?.length">
          <v-card-title>Eventi registrati</v-card-title>
          <v-timeline density="compact" class="pa-4">
            <v-timeline-item
              v-for="event in selectedProduct.events"
              :key="event.id"
              :dot-color="event.is_on_chain ? 'green' : 'orange'"
              size="small"
            >
              <div class="font-weight-bold">{{ event.event_type_label || event.event_type }}</div>
              <div class="text-caption">{{ event.description }}</div>
              <div class="text-caption text-grey" v-if="event.document_hash">
                Hash: {{ event.document_hash?.slice(0, 16) }}...
              </div>
              <div  class="text-caption text-grey">
                Timestamp: {{ event.timestamp }}
              </div>
              <SolanaExplorerLink :pda-address="event.pda_address" size="small" class="mt-1" block>
                Verifica dati evento
              </SolanaExplorerLink>
              <div class="mt-2">
                <v-btn append-icon="mdi-folder-information-outline" variant="flat" size="small" color="secondary" block>
                  Vedi dati
                  <v-dialog activator="parent" max-width="min(600px, 90vw)">
                    <EventCard :event />
                  </v-dialog>
                </v-btn>
              </div>
            </v-timeline-item>
          </v-timeline>
        </v-card>
      </template>

      <!-- Placeholder -->
      <v-card v-else>
        <v-card-text class="text-center text-grey pa-8">
          <v-icon size="64" class="mb-4">mdi-package-variant-closed</v-icon>
          <p>Seleziona un prodotto o creane uno nuovo</p>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup>
import { ref, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import ProductList from '@/components/ProductList.vue'
import EventForm from '@/components/EventForm.vue'
import SolanaExplorerLink from '@/components/SolanaExplorerLink.vue'
import Layout from '../Layout.vue'
import PassportRequestButton from '@/components/PassportRequestButton.vue'
import ProductForm from '@/components/ProductForm.vue'
import EventCard from '@/components/EventCard.vue'

const props = defineProps({
  user: Object,
  products: Object,
  productTypes: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const selectedProduct = ref(null)
const showMissingEvents = ref(false)
const passportButtonRef = ref(null)

const eventTypeLabels = {
  ORIGIN: 'Origine',
  PRODUCTION: 'Produzione',
  TRANSPORT: 'Trasporto',
  ENV_CLAIM: 'Dich. Ambientale'
}

const selectProduct = (product) => {
  selectedProduct.value = product
  showMissingEvents.value = false
}

const onProductUpdated = (product) => {
  if (selectedProduct.value?.id === product.id) {
    selectedProduct.value = product
  }
}

const onProductUpdatedFromDetail = (product, isActive) => {
  selectedProduct.value = product
  isActive.value = false
  router.reload({ only: ['products'] })
}

const onEventCreated = (event, isActive) => {
  router.reload({
    only: ['products'],
    onSuccess: () => {
      // Aggiorna il prodotto selezionato con i nuovi dati
      const products = props.products.data || props.products
      const updated = products.find(p => p.id === event.product_id)
      if (updated) {
        selectedProduct.value = updated
      }
    }
  })
  isActive.value = false
}

const onPassportIssued = (passport) => {
  router.reload({
    only: ['products'],
    onSuccess: () => {
      const products = props.products.data || props.products
      const updated = products.find(p => p.id === passport.product_id)
      if (updated) {
        selectedProduct.value = updated
      }
    }
  })
}

const scrollToPassportButton = async (product) => {
  selectProduct(product)
  await nextTick()
  passportButtonRef.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

defineOptions({ layout: Layout })
</script>
