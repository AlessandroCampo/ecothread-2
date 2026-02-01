<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      Prodotti
      <v-spacer />
      <v-btn color="primary" size="small">
        <v-icon left>mdi-plus</v-icon> Nuovo
        <v-dialog activator="parent" max-width="min(600px, 90vw)" persistent>
          <template #default="{isActive}">
            <ProductForm
                @cancel="isActive.value = false"
                @created="(newProduct) =>onProductCreated(newProduct, isActive)"
                :productTypes
              />
          </template>
        </v-dialog>
      </v-btn>
    </v-card-title>

    <v-list v-if="products.length">
      <v-list-item
        v-for="product in products"
        :key="product.id"
        @click="$emit('select', product)"
      >
        <template v-slot:prepend>
          <v-icon :color="product.tx_signature ? 'green' : 'orange'">
            {{ product.tx_signature ? 'mdi-check-circle' : 'mdi-clock' }}
          </v-icon>
        </template>
        <template #append>
          <div class="pa-2">
            <v-img :src="product.image_url" width="40" aspect-ratio="1/2"/>
          </div>
        </template>

        <v-list-item-title>{{ product.name }}</v-list-item-title>
        <v-list-item-subtitle>
          {{ product.id }} · {{ product.events?.length || 0 }} eventi
        </v-list-item-subtitle>
      </v-list-item>
    </v-list>

    <v-card-text v-else class="text-center text-grey">
      Nessun prodotto
    </v-card-text>
  </v-card>
</template>

<script setup>

import ProductForm from '@/components/ProductForm.vue'
import { router } from '@inertiajs/vue3'


const props = defineProps({
  products: Array,
   productTypes: {
    default: []
  }
})

const onProductCreated = (product, isActive) => {
        props.products.push(product);
        isActive.value = false;
}

defineEmits(['create', 'select'])
</script>