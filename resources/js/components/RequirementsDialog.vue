<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2" color="warning">mdi-clipboard-check-outline</v-icon>
      Requisiti Passaporto
    </v-card-title>

    <v-card-text>
      <v-alert
        type="info"
        variant="tonal"
        density="compact"
        class="mb-4"
      >
        Completa tutti i requisiti per ottenere il Passaporto Digitale
      </v-alert>

      <v-list density="compact">
        <v-list-item>
          <template #prepend>
            <v-icon :color="product.is_on_chain ? 'success' : 'grey'">
              {{ product.is_on_chain ? 'mdi-check-circle' : 'mdi-circle-outline' }}
            </v-icon>
          </template>
          <v-list-item-title>Prodotto registrato on-chain</v-list-item-title>
          <v-list-item-subtitle v-if="!product.is_on_chain" class="text-warning">
            Attendi la conferma blockchain
          </v-list-item-subtitle>
        </v-list-item>

        <v-divider class="my-2" />

        <v-list-item
          v-for="req in requirements"
          :key="req.type"
        >
          <template #prepend>
            <v-icon :color="req.satisfied ? 'success' : 'grey'">
              {{ req.satisfied ? 'mdi-check-circle' : 'mdi-circle-outline' }}
            </v-icon>
          </template>

          <v-list-item-title>{{ req.label }}</v-list-item-title>
          <v-list-item-subtitle class="text-caption">{{ req.description }}</v-list-item-subtitle>

          <template #append>
            <v-btn
              v-if="!req.satisfied && product.is_on_chain"
              size="small"
              color="primary"
              variant="tonal"
              @click="$emit('add-event', req.type)"
            >
              <v-icon size="small">mdi-plus</v-icon>
              Aggiungi
            </v-btn>
            <v-chip v-else-if="req.satisfied" size="x-small" color="success" variant="flat">
              Completato
            </v-chip>
          </template>
        </v-list-item>
      </v-list>

      <v-alert
        v-if="allSatisfied"
        type="success"
        variant="tonal"
        density="compact"
        class="mt-4"
      >
        <div class="d-flex align-center justify-space-between">
          <span>Tutti i requisiti sono soddisfatti!</span>
          <v-btn
            size="small"
            color="success"
            variant="flat"
            @click="$emit('request-passport')"
          >
            Richiedi Passaporto
          </v-btn>
        </div>
      </v-alert>
    </v-card-text>

    <v-card-actions>
      <v-spacer />
      <v-btn variant="text" @click="$emit('close')">Chiudi</v-btn>
    </v-card-actions>
  </v-card>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
})

defineEmits(['close', 'add-event', 'request-passport'])

const REQUIRED_EVENT_TYPES = [
  { type: 'ORIGIN', label: 'Origine Materie Prime', description: 'Provenienza e composizione materiali' },
  { type: 'PRODUCTION', label: 'Produzione', description: 'Processo produttivo e luogo di fabbricazione' },
  { type: 'TRANSPORT', label: 'Trasporto', description: 'Logistica e modalità di trasporto' },
  { type: 'ENV_CLAIM', label: 'Dichiarazione Ambientale', description: 'Claim di sostenibilità del brand' }
]

const requirements = computed(() => {
  const events = props.product.events || []
  return REQUIRED_EVENT_TYPES.map(req => ({
    ...req,
    satisfied: events.some(e => e.event_type === req.type && e.is_on_chain)
  }))
})

const allSatisfied = computed(() => {
  return props.product.is_on_chain && requirements.value.every(r => r.satisfied)
})
</script>
