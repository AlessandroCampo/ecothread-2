<template>
  <v-card class="pb-0">
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2">mdi-timeline-plus</v-icon>
      Nuovo Evento
      <v-spacer />
      <v-chip 
        v-if="isWalletConnected" 
        color="success" 
        size="small"
        variant="tonal"
      >
        <v-icon start size="small">mdi-wallet</v-icon>
        Wallet Connesso
      </v-chip>
    </v-card-title>

    <v-card-text >
       <wallet-alert/>
        <!-- Product ID (readonly se passato come prop) -->

    
        <v-text-field
          v-model="form.product_id"
          label="Product ID"
          readonly
          disabled
          prepend-inner-icon="mdi-package-variant"
          class="mb-3"
          :error-messages="errors.product_id"
        >
       <template #prepend-inner>
          <solana-icon />
        </template>
      </v-text-field>
  
        <!-- Event Type -->
       <v-select
  v-model="form.event_type"
  :items="groupedEventTypes"
  item-title="label"
  item-value="value"
  label="Tipo Evento*"
  variant="outlined"
  class="mb-3"
  :disabled="!isWalletConnected"
  :error-messages="errors.event_type"
>
  <template #item="{ item, props }">
    <v-list-subheader v-if="item.raw.header">
      {{ item.raw.header }}
    </v-list-subheader>
    <v-list-item v-else v-bind="props">
      <template #prepend>
        <v-icon :color="item.raw.color" class="mr-2">
          {{ item.raw.icon }}
        </v-icon>
      </template>
      <template #subtitle>
        <p class="pe-4">

          {{ item.raw.description }}
        </p>
      </template>
      <template #append>
        <v-chip 
          v-if="item.raw.is_required" 
          size="x-small" 
          color="primary" 
          variant="tonal"
        >
          <v-icon icon="mdi-certificate"/>
        </v-chip>
      </template>
    </v-list-item>
  </template>

  <template #selection="{ item }">
    <v-icon :color="item.raw.color" class="mr-2" size="small">
      {{ item.raw.icon }}
    </v-icon>
    {{ item.raw.label }}
  </template>
</v-select>

 <!-- Description / Notes -->
        <v-textarea
          v-model="form.description"
          label="Descrizione / Note"
          rows="2"
          variant="outlined"
          class="mb-3"
          :disabled="!isWalletConnected"
        />
  
        <!-- Trust Level Indicator -->
        <v-select
          v-model="form.trust_level"
          :items="trustLevels"
          item-title="label"
          item-value="value"
          label="Livello di Attendibilità*"
          variant="outlined"
          class="mb-3"
          :disabled="!isWalletConnected"
          hint="Come classifichi la fonte di questa informazione?"
          persistent-hint
        >
          <template #item="{ item, props }">
            <v-list-item v-bind="props">
              <template #prepend>
                <v-icon :color="item.raw.color" size="small">
                  {{ item.raw.icon }}
                </v-icon>
              </template>
              <template #subtitle>
                {{ item.raw.description }}
              </template>
            </v-list-item>
          </template>
        </v-select>
  
       
  
       
  
        <!-- Document Upload -->
        <v-file-input
          v-model="form.document"
          label="Documento di supporto"
          variant="outlined"
          prepend-icon="mdi-file-document"
          accept=".pdf,.jpg,.jpeg,.png"
          class="mb-3"
          :disabled="!isWalletConnected"
          hint="PDF o immagine del certificato/report"
          persistent-hint
          @update:model-value="onDocumentChange"
        />

         <v-divider v-if="form.event_type" class="my-4" />
  
  <event-metadata-form
    :event-type="form.event_type"
    :metadata="form.metadata"
    :disabled="!isWalletConnected"
      :errors="errors"

  />

          <v-expand-transition>
        <v-alert 
          v-if="documentHash" 
          type="info" 
          variant="tonal" 
          density="compact"
          class="mb-4"
        >
          <div class="text-caption">
            <strong>SHA-256 Hash:</strong><br>
            <code>{{ documentHash }}</code>
          </div>
          <div class="text-caption mt-1 text-medium-emphasis">
            Questo hash sarà registrato on-chain per verificare l'integrità del documento.
          </div>
        </v-alert>
      </v-expand-transition>

        <v-alert
  color="warning"
  variant="tonal"
  density="compact"
  class="mb-2"
>
  <template #prepend>
    <v-checkbox
      v-model="confirmedImmutability"
      density="compact"
      hide-details
      class="mr-n2"
    />
  </template>
  <div class="text-body-2">
    <strong>Operazione irreversibile:</strong> una volta registrato su blockchain, 
    l'evento non potrà essere modificato o eliminato.
  </div>
</v-alert>
      <!-- Alert: Wallet non connesso -->

      <!-- Document Hash Preview -->
    

      <v-divider class="my-4" />

      <!-- Transaction Progress -->
      <v-expand-transition>
        <div v-if="transactionStep">
          <v-alert 
            :type="transactionStep === 'error' ? 'error' : 'info'" 
            variant="tonal"
            class="mb-4"
          >
            <template #prepend>
              <v-progress-circular 
                v-if="!['success', 'error'].includes(transactionStep)"
                indeterminate 
                size="20" 
                width="2"
              />
              <v-icon v-else-if="transactionStep === 'success'" color="success">
                mdi-check-circle
              </v-icon>
              <v-icon v-else color="error">
                mdi-alert-circle
              </v-icon>
            </template>
            <div class="text-body-2">
              <span v-if="transactionStep === 'hashing'">
                🔐 Calcolo hash del documento...
              </span>
              <span v-else-if="transactionStep === 'uploading'">
                📤 Upload documento su IPFS...
              </span>
              <span v-else-if="transactionStep === 'signing'">
                ✍️ Firma la transazione nel wallet...
              </span>
              <span v-else-if="transactionStep === 'confirming'">
                ⏳ Attendo conferma blockchain...
              </span>
              <span v-else-if="transactionStep === 'saving'">
                💾 Salvataggio metadata...
              </span>
              <span v-else-if="transactionStep === 'success'">
                ✅ Evento registrato on-chain!
              </span>
              <span v-else-if="transactionStep === 'error'">
                ❌ {{ error }}
              </span>
            </div>
          </v-alert>
        </div>
      </v-expand-transition>

      <!-- Error Alert -->
      <v-alert 
        v-if="error && !transactionStep" 
        type="error" 
        class="mt-4" 
        closable 
        @click:close="error = null"
      >
        {{ error }}
      </v-alert>

    </v-card-text>

   <v-card-actions class="d-flex flex-column flex-md-row justify-md-end ga-2 mt-0">
  <template v-if="!result">
    <v-btn 
      @click="$emit('cancel')" 
      :disabled="loading" 
      color="error" 
      variant="flat" 
      :block="mobile"
    >
      Annulla
    </v-btn>
    <v-btn
      color="primary"
      :loading="loading"
      :disabled="!isValid || !isWalletConnected"
      @click="submit"
      variant="flat"
      :block="mobile"
    >
      Registra Evento
      <template #append>
        <SolanaIcon />
      </template>
    </v-btn>
  </template>
  <template v-else>
    <v-btn 
      @click="$emit('cancel')" 
      variant="flat" 
      :disabled="loading" 
      append-icon="mdi-check" 
      color="primary"
      :block="mobile"
    >
      Fatto
    </v-btn>
  </template>
</v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import api from '@/lib/axios'
import { useSolana } from '@/composables/useSolana'
import { useEnums } from '@/composables/useEnum'
import { route } from 'ziggy-js'
import WalletAlert from './WalletAlert.vue'
import { useSnack } from '@/composables/useSnack'
import SolanaIcon from './SolanaIcon.vue'
import EventMetadataForm from './EventMetadataForm.vue'
import { useDisplay } from 'vuetify/lib/composables/display.mjs'

const { success: showSuccess, error: showError } = useSnack()
const {mobile} = useDisplay();

// ============================================
// Solana Integration
// ============================================
const { 
  addEvent: addEventOnChain, 
  loading: solanaLoading,
  isWalletConnected,
  getTxExplorerUrl,
  getAddressExplorerUrl,
  hashFile,
  fetchProduct,
  hashMetadata
} = useSolana()

const emit = defineEmits(['cancel', 'created'])

const {eventTypes, trustLevels} =  useEnums();

const props = defineProps({
  productId: {
    type: String,
    default: ''
  },
  currentEventCount: {
    type: Number,
    default: 0
  },
  initialEventType: {
    type: String,
    default: null
  }
})

// ============================================
// State
// ============================================
const error = ref<string | null>(null)
const errors = ref<Record<string, string[]>>({})
const loading = ref(false)
const transactionStep = ref<string | null>(null)
const documentHash = ref<string | null>(null)
const documentUri = ref<string | null>(null)

const form = ref({
  product_id: props.productId,
  event_type: props.initialEventType as string | null,
  trust_level: 'autodeclaration',
  description: '',
  document: null as File | null,
  metadata: {} as Record<string, any>
})

const result = ref<{
  txSignature: string
  pdaAddress: string
  eventIndex: number
} | null>(null)

const confirmedImmutability = ref(false)

function getDefaultMetadata(eventType: string | null): Record<string, any> {
  switch (eventType) {
    case 'ORIGIN':
      return { country: null, region: '', compositions: [{ material: null, percentage: 100, certification: '', is_recycled: false }] }
    case 'PRODUCTION':
      return { processes: [], water_usage_liters: null, energy_kwh: null }
    case 'TRANSPORT':
      return { origin_country: null, destination_country: null, transport_mode: null, distance_km: null, co2_kg: null }
    case 'PACKAGING':
      return { materials: [], is_recyclable: false }
    case 'RECYCLE':
      return { recycle_percentage: 0, take_back_program: false }
    case 'CERTIFICATION':
      return { certification_type: null, issued_by: '', valid_until: null, certificate_number: '' }
    default:
      return {}
  }
}

// Sync product_id se cambia la prop
watch(() => props.productId, (newVal) => {
  form.value.product_id = newVal
})

watch(() => form.value.event_type, (newType) => {
  form.value.metadata = getDefaultMetadata(newType)
})

// ============================================
// Computed
// ============================================
const isValid = computed(() => {
  return form.value.product_id && form.value.event_type && confirmedImmutability.value
})

const explorerUrl = computed(() => {
  if (!result.value?.txSignature) return '#'
  return getTxExplorerUrl(result.value.txSignature)
})

// ============================================
// Methods
// ============================================

/**
 * Handler per cambio documento
 */
async function onDocumentChange(file: File | null) {
  if (file) {
    try {
      documentHash.value = await hashFile(file)
    } catch (e) {
      console.error('Error calculating hash:', e)
      documentHash.value = null
    }
  } else {
    documentHash.value = null
  }
}


// EventForm.vue - submit() con pattern draft-first

const draftEventId = ref<number | null>(null)

async function rollbackDraft(eventId: number) {
  try {
    await api.delete(`/admin/events/${eventId}`)
  } catch (e) {
    console.error('Rollback failed:', e)
  }
}

const groupedEventTypes = computed(() => {
  const required = eventTypes.value.filter(e => e.is_required)
  const optional = eventTypes.value.filter(e => !e.is_required)
  
  return [
    { header: 'Obbligatori per certificazione', value: 'header-required', disabled: true },
    ...required,
    { header: 'Opzionali', value: 'header-optional', disabled: true },
    ...optional,
  ]
})

async function submit() {
  if (loading.value || !isWalletConnected.value) return

  loading.value = true
  error.value = null
  errors.value = {}
  result.value = null
  transactionStep.value = null
  draftEventId.value = null

  try {
    // ============================================
    // STEP 1: Hash documento (se presente)
    // ============================================
    let dataHash = documentHash.value || ''
    
    if (form.value.document && !documentHash.value) {
      transactionStep.value = 'hashing'
      dataHash = await hashFile(form.value.document)
      documentHash.value = dataHash
    }

    if (!dataHash && form.value.description) {
      const encoder = new TextEncoder()
      const data = encoder.encode(form.value.description)
      const hashBuffer = await crypto.subtle.digest('SHA-256', data)
      const hashArray = Array.from(new Uint8Array(hashBuffer))
      dataHash = hashArray.map(b => b.toString(16).padStart(2, '0')).join('')
    }

    // ============================================
    // STEP 2: Upload su Pinata (se c'è documento)
    // ============================================
    let ipfsUri: string | null = null

    if (form.value.document) {
      transactionStep.value = 'uploading'
      
      const uploadFormData = new FormData()
      uploadFormData.append('document', form.value.document)
      uploadFormData.append('product_id', form.value.product_id)

      try {

        const url = route('admin.events.upload_document', props.productId);

        const { data: uploadResult } = await api.post(
          url,
          uploadFormData,
          { headers: { 'Content-Type': 'multipart/form-data' } }
        )
        if (uploadResult.success) {
          ipfsUri = uploadResult.uri
          documentUri.value = ipfsUri
        }
      } catch (e) {
        console.warn('Pinata upload failed, continuing without IPFS URI')
      }
    }

    // ============================================
    // STEP 3: Salva Draft nel Database
    // ============================================
    transactionStep.value = 'saving'

    const draftFormData = new FormData()
    draftFormData.append('event_type', form.value.event_type!)
    draftFormData.append('trust_level', form.value.trust_level)
    draftFormData.append('document_hash', dataHash)
    if (Object.keys(form.value.metadata).length > 0) {
  draftFormData.append('metadata', JSON.stringify(form.value.metadata))
}
    
    if (form.value.description) {
      draftFormData.append('description', form.value.description)
    }
    if (form.value.document) {
      draftFormData.append('document', form.value.document)
    }
    if (ipfsUri) {
      draftFormData.append('document_uri', ipfsUri)
    }

    const { data: draftData } = await api.post(
      route('admin.events.store', props.productId),
      draftFormData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    )

    draftEventId.value = draftData.event.id

    // ============================================
    // STEP 4: Scrittura On-Chain
    // ============================================
    transactionStep.value = 'signing'

    const productResult = await fetchProduct(form.value.product_id)
    if (!productResult.success) {
      await rollbackDraft(draftEventId.value)
      transactionStep.value = 'error'
      error.value = 'Prodotto non trovato on-chain'
      loading.value = false
      return
    }

    const eventIndex = productResult.data.eventCount
const metadataHash = await hashMetadata(form.value.metadata)

    // Passa anche documentUri (già supportato dal composable)
    const onChainResult = await addEventOnChain(
      form.value.product_id,
      eventIndex,
      form.value.event_type!,
      dataHash,
      ipfsUri || '',
       metadataHash    // ← Aggiungi questo parametro
    )

    if (!onChainResult.success) {
      await rollbackDraft(draftEventId.value)
      transactionStep.value = 'error'
      error.value = onChainResult.error || 'Errore nella transazione blockchain'
      loading.value = false
      return
    }

    transactionStep.value = 'confirming'
    await new Promise(resolve => setTimeout(resolve, 500))

    // ============================================
    // STEP 5: Conferma nel Database
    // ============================================
   const { data: confirmData } = await api.patch(`/admin/events/${draftEventId.value}/confirm`, {
  index: eventIndex,
  timestamp: Math.floor(Date.now() / 1000),
  pda_address: onChainResult.pdaAddress,
  tx_signature: onChainResult.txSignature,
})

    // ============================================
    // STEP 6: Success
    // ============================================
    transactionStep.value = 'success'

result.value = {
  txSignature: onChainResult.txSignature!,
  pdaAddress: onChainResult.pdaAddress!,
  eventIndex: eventIndex,
}

showSuccess(`Evento registrato on-chain!`, {
  text: 'Vedi su Solana',
  href: getAddressExplorerUrl(onChainResult.pdaAddress!),
})

emit('created', confirmData.event);

  } catch (e: any) {
    // Rollback se abbiamo un draft
    if (draftEventId.value) {
      await rollbackDraft(draftEventId.value)
    }

    transactionStep.value = 'error'
    
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      error.value = 'Correggi gli errori nel form'
    } else {
      error.value = e.response?.data?.message || e.message || 'Errore durante la registrazione'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
code {
  background-color: rgba(var(--v-theme-surface-variant), 0.5);
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.75em;
  word-break: break-all;
}
</style>