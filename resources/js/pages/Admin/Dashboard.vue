<template>

        <v-row>
          <!-- Colonna sinistra: lista prodotti -->
          <v-col cols="12" md="5">
            <ProductList
              :products="products"
              :productTypes
              @select="selectProduct"
            />
          </v-col>

          <!-- Colonna destra: dettaglio / form -->
          <v-col cols="12" md="7">
            <!-- Form nuovo prodotto -->
        
            <!-- Dettaglio prodotto selezionato -->
            <template v-if="selectedProduct">
              <v-card class="mb-4">
                <v-card-title>
                  {{ selectedProduct.name }}
                  <v-chip
                    :color="selectedProduct.tx_signature ? 'green' : 'orange'"
                    size="small"
                    class="ml-2"
                  >
                    {{ selectedProduct.tx_signature ? 'On-chain' : 'Pending' }}
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
               

                </v-card-text>
                <v-card-actions class="d-flex justify-end ga-2 flex-column align-end">
                    <SolanaExplorerLink :pda-address="selectedProduct.pda_address" block color="info">Vedi on-chain</SolanaExplorerLink>
                    <v-btn block color="secondary" variant="flat" append-icon="mdi-store-edit">
                         MODIFICA {{ selectedProduct?.id }}
                                    <v-dialog activator="parent" max-width="min(600px, 90vw)" persistent>
                            <template #default="{isActive}">
                              <ProductForm
                                  @cancel="isActive.value = false"
                                  :product="selectedProduct"
                                  :productTypes
                                  @updated="(product) => onProductUpdated(product, isActive)"
                                />
                            </template>
                </v-dialog>
                    </v-btn>
                  <v-btn
                    color="primary-darken-3"
                    append-icon="mdi-plus"
                    variant="flat"
                    block
                  >
                    <v-dialog activator="parent" width="min(600px, 95vw)" :persistent="true">
                      <template #default="{isActive}">
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
                  <PassportRequestButton
                  :product="selectedProduct"
                  @passport-issued="onPassportIssued"
                />
                </v-card-actions>
              </v-card>


              <!-- Lista eventi -->
               
               <v-card v-if="selectedProduct.events?.length">
                <v-card-title>Eventi registrati</v-card-title>
                <v-timeline density="compact" class="pa-4">
                  <v-timeline-item
                    v-for="event in selectedProduct.events"
                    :key="event.id"
                    :dot-color="event.tx_signature ? 'green' : 'orange'"
                    size="small"
                  >
                    <div class="font-weight-bold">{{ event.event_type }}</div>
                    <div class="text-caption">{{ event.description }}</div>
                    <div class="text-caption text-grey" v-if="event.document_hash">
                      Hash: {{ event.document_hash?.slice(0, 16) }}...
                    </div>
                   <SolanaExplorerLink :pda-address="event.pda_address" size="small" class="mt-1">
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
                Seleziona un prodotto o creane uno nuovo
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import api from '@/lib/axios'
import ProductList from '@/components/ProductList.vue'
import EventForm from '@/components/EventForm.vue'
import SolanaExplorerLink from '@/components/SolanaExplorerLink.vue'
import Layout from '../Layout.vue'
import PassportRequestButton from '@/components/PassportRequestButton.vue'
import ProductForm from '@/components/ProductForm.vue'
import EventCard from '@/components/EventCard.vue'


const props = defineProps({
  user: Object,
  products: Array,
  productTypes: {
    default: []
  }
})

const selectedProduct = ref(null)
const products = ref(props.products);


const selectProduct = (product) => {
  selectedProduct.value = product
}

const onEventCreated = (event, isActive) => {
  const index = products.value.findIndex(p => p.id === event.product_id)
  
  if (index !== -1) {
    if (!products.value[index].events) {
      products.value[index].events = []
    }
    products.value[index].events.push(event)
  }
  
  isActive.value = false
}

const onPassportIssued = (passport) => {
 const index = products.value.findIndex(p => p.id === passport.product_id)
  
  if (index !== -1) {
    products.value[index].passport = passport;
  }
}

defineOptions({ layout: Layout })


</script>
