<template>
  <div class="w-100">
    <!-- Stato: Passaporto esistente -->
    <v-card v-if="hasPassport" variant="tonal" color="success" class="pa-3">
      <div class="d-flex align-center">
        <v-icon color="success" class="mr-2">mdi-passport</v-icon>
        <div class="flex-grow-1">
          <div class="text-subtitle-2">Passaporto Attivo</div>
          <div class="text-caption">{{ product.passport.passport_number }}</div>
        </div>
        <v-btn
          size="small"
          variant="text"
          color="success"
          @click="showPassportDialog = true"
        >
          Visualizza
          <v-icon end size="small">mdi-eye</v-icon>
        </v-btn>
      </div>
    </v-card>

    <!-- Stato: Requisiti mancanti (default se eventi incompleti) -->
    <v-btn
      v-else-if="!meetsRequirements"
      block
      color="warning"
      variant="flat"
      @click="showEligibilityDialog = true"
    >
      <template #prepend>
        <v-icon>mdi-alert-circle</v-icon>
      </template>
      Requisiti mancanti ({{ completedRequirements }}/{{ totalRequirements }})
    </v-btn>

    <!-- Stato: Idoneo, può richiedere -->
    <v-btn
      v-else
      block
      color="success"
      variant="flat"
      :loading="loading"
      @click="handleClick"
    >
      <template #append>
        <v-icon>mdi-passport</v-icon>
      </template>
      Richiedi Passaporto
     
    </v-btn>

    <!-- Dialog: Visualizza Passaporto (placeholder) -->
      <passport-dialog v-model="showPassportDialog" :product="product" />


    <!-- Dialog: Verifica eligibilità -->
    <v-dialog v-model="showEligibilityDialog" max-width="500">
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon class="mr-2">mdi-clipboard-check-outline</v-icon>
          Requisiti Passaporto
        </v-card-title>

        <v-card-text>
          <v-alert
            v-if="meetsRequirements"
            type="success"
            variant="tonal"
            class="mb-4"
          >
            <v-alert-title>Prodotto idoneo!</v-alert-title>
            Tutti i requisiti sono soddisfatti. Puoi richiedere il passaporto.
          </v-alert>

          <v-alert
            v-else
            type="warning"
            variant="tonal"
            class="mb-4"
          >
            <v-alert-title>Requisiti incompleti</v-alert-title>
            Registra gli eventi mancanti on-chain per ottenere il passaporto.
          </v-alert>

          <!-- Checklist requisiti -->
          <v-list density="compact" class="bg-transparent">
            <!-- Prodotto on-chain -->
            <v-list-item>
              <template #prepend>
                <v-icon :color="product.is_on_chain ? 'success' : 'grey'">
                  {{ product.is_on_chain ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                </v-icon>
              </template>
              <v-list-item-title>Prodotto registrato on-chain</v-list-item-title>
            </v-list-item>

            <!-- Eventi richiesti -->
            <v-list-item
              v-for="req in requirementStatus"
              :key="req.type"
            >
              <template #prepend>
                <v-icon :color="req.satisfied ? 'success' : 'grey'">
                  {{ req.satisfied ? 'mdi-check-circle' : 'mdi-circle-outline' }}
                </v-icon>
              </template>
              <v-list-item-title>
                {{ req.label }}
              </v-list-item-title>
              <v-list-item-subtitle v-if="req.event && !req.event.is_on_chain" class="text-warning">
                Presente ma non ancora on-chain
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>

          <!-- Info aggiuntiva se verificato con API -->
          <v-alert
            v-if="apiEligibility?.errors?.length"
            type="error"
            variant="tonal"
            class="mt-4"
            density="compact"
          >
            <div class="text-subtitle-2 mb-1">Problemi rilevati:</div>
            <ul class="text-caption pl-4">
              <li v-for="(error, i) in apiEligibility.errors" :key="i">{{ error }}</li>
            </ul>
          </v-alert>
        </v-card-text>

        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showEligibilityDialog = false">
            Chiudi
          </v-btn>
          <v-btn
            v-if="meetsRequirements"
            color="primary"
            variant="flat"
            width="250"
            :loading="requesting"
            @click="requestPassport"
            append-icon="mdi-passport"
          >
            Richiedi Passaporto
            
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog: Successo rilascio -->
    <v-dialog v-model="showSuccessDialog" max-width="450" persistent>
      <v-card>
        <v-card-text class="text-center pa-6">
          <v-icon color="success" size="64" class="mb-4">mdi-passport</v-icon>
          <h2 class="text-h5 mb-2">Passaporto Rilasciato!</h2>
          <p class="text-body-2 text-medium-emphasis mb-4">
            Il tuo prodotto ha ottenuto il Passaporto Digitale EcoThread
          </p>
          
          <v-card variant="outlined" class="pa-4 mb-4">
            <div class="text-overline text-medium-emphasis">Numero Passaporto</div>
            <div class="text-h6 font-weight-bold">{{ newPassport?.passport_number }}</div>
          </v-card>

           <v-btn
          size="small"
          variant="flat"
          color="success"
          class="my-2"
          @click="showPassportDialog = true; showSuccessDialog= false"
          block
        >
          Visualizza
          <v-icon end size="small">mdi-eye</v-icon>
        </v-btn>

          <v-btn
            color="primary"
            variant="flat"
            class="mb-2"
            block
          >
            <v-icon start>mdi-open-in-new</v-icon>
            Vedi Pagina Pubblica
          </v-btn>
          
          <v-btn
            variant="text"
            block
            @click="closeSuccessDialog"
          >
            Chiudi
          </v-btn>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Snackbar errori -->
    <v-snackbar v-model="showError" color="error" :timeout="5000">
      {{ errorMessage }}
      <template #actions>
        <v-btn variant="text" @click="showError = false">Chiudi</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import api from '@/lib/axios'
import { route } from 'ziggy-js'
import { useSolana } from '@/composables/useSolana'
import PassportDialog from './PassportDialog.vue'

// ============================================
// Types
// ============================================
interface Event {
  id: number
  event_type: string
  is_on_chain: boolean
  pda_address?: string 
  document_hash?: string
}

interface Passport {
  id: number
  passport_number: string
  status: string
  verified_at: string
}

interface Product {
  id: string
  name: string
  is_on_chain: boolean
  pda_address?: string
  events?: Event[]
  passport?: Passport | null
}

// ============================================
// Props & Emits
// ============================================
const { fetchProduct, fetchEvent } = useSolana()

const props = defineProps<{
  product: Product
}>()

const emit = defineEmits<{
  (e: 'passport-issued', passport: Passport): void
}>()

// ============================================
// Constants
// ============================================
const REQUIRED_EVENT_TYPES = [
  { type: 'ORIGIN', label: 'Origine Materie Prime' },
  { type: 'PRODUCTION', label: 'Produzione / Lavorazione' },
  { type: 'TRANSPORT', label: 'Trasporto' },
  { type: 'ENV_CLAIM', label: 'Dichiarazione Ambientale' },
]

// ============================================
// State
// ============================================
const loading = ref(false)
const requesting = ref(false)
const showPassportDialog = ref(false)
const showEligibilityDialog = ref(false)
const showSuccessDialog = ref(false)
const showError = ref(false)
const errorMessage = ref('')

const apiEligibility = ref<{
  eligible: boolean
  errors: string[]
} | null>(null)

const newPassport = ref<Passport | null>(null)

// ============================================
// Computed
// ============================================

/** Verifica se il prodotto ha già un passaporto valido */
const hasPassport = computed(() => {
  return props.product.passport && 
         ['verified', 'pending'].includes(props.product.passport.status)
})

/** Calcola lo stato di ogni requisito basandosi sugli eventi del prodotto */
const requirementStatus = computed(() => {
  const events = props.product.events || []
  
  return REQUIRED_EVENT_TYPES.map(req => {
    const event = events.find(e => e.event_type === req.type)
    return {
      ...req,
      event,
      satisfied: !!event && event.is_on_chain,
    }
  })
})

/** Conta quanti requisiti sono soddisfatti */
const completedRequirements = computed(() => {
  let count = props.product.is_on_chain ? 1 : 0
  count += requirementStatus.value.filter(r => r.satisfied).length
  return count
})

/** Totale requisiti (prodotto + eventi) */
const totalRequirements = computed(() => {
  return 1 + REQUIRED_EVENT_TYPES.length // 1 per prodotto + eventi
})

/** Verifica se tutti i requisiti sono soddisfatti */
const meetsRequirements = computed(() => {
  if (!props.product.is_on_chain) return false
  return requirementStatus.value.every(r => r.satisfied)
})

/** URL pagina pubblica passaporto esistente */
const verificationUrl = computed(() => {
  if (props.product.passport?.passport_number) {
    return route('passport.verify', { passportNumber: props.product.passport.passport_number })
  }
  return '#'
})

/** URL pagina pubblica nuovo passaporto */
const newPassportUrl = computed(() => {
  if (newPassport.value?.passport_number) {
    return route('passport.verify', { passportNumber: newPassport.value.passport_number })
  }
  return '#'
})

async function verifyOnChain(): Promise<{ valid: boolean; errors: string[] }> {
  const errors: string[] = []
  
  try {
    // 1. Verifica prodotto on-chain
    if (!props.product.pda_address) {
      errors.push('Prodotto non ha PDA address')
      return { valid: false, errors }
    }
    
    const onChainProduct = await fetchProduct(props.product.id)
    if (!onChainProduct.success) {
      errors.push('Prodotto non trovato sulla blockchain')
      return { valid: false, errors }
    }
    
    // 2. Verifica ogni evento richiesto on-chain
    const events = props.product.events || []
    
    for (const req of REQUIRED_EVENT_TYPES) {
      const dbEvent = events.find(e => e.event_type === req.type && e.is_on_chain)
      
      if (!dbEvent) {
        errors.push(`Evento ${req.label} non presente`)
        continue
      }
      
      if (!dbEvent.pda_address) {
        errors.push(`Evento ${req.label} non ha PDA address`)
        continue
      }
      
      // Fetch evento dalla blockchain
      const onChainEvent = await fetchEvent(dbEvent.pda_address)
      
      if (!onChainEvent.success || !onChainEvent.data) {
        errors.push(`Evento ${req.label} non trovato on-chain`)
        continue
      }
      
      // Confronta hash documento (se presente)
      if (dbEvent.document_hash && onChainEvent.data.documentHash) {
        if (dbEvent.document_hash !== onChainEvent.data.documentHash) {
          errors.push(`Evento ${req.label}: hash documento non corrisponde`)
        }
      }
    }
    
    return { valid: errors.length === 0, errors }
    
  } catch (e: any) {
    errors.push(`Errore verifica blockchain: ${e.message}`)
    return { valid: false, errors }
  }
}

// ============================================
// Methods
// ============================================

async function handleClick() {
  if (meetsRequirements.value) {
    // Verifica con API prima di mostrare dialog
    await verifyWithApi()
    showEligibilityDialog.value = true
  }
}

async function verifyWithApi() {
  loading.value = true
  
  try {
    const { data } = await api.get(
      route('passports.check-eligibility', { productId: props.product.id })
    )
    apiEligibility.value = data.verification
  } catch (e: any) {
    console.error('Eligibility check failed:', e)
  } finally {
    loading.value = false
  }
}

async function requestPassport() {
  requesting.value = true
  
  try {
    const chainVerification = await verifyOnChain()

     if (!chainVerification.valid) {
      errorMessage.value = 'Verifica blockchain fallita: ' + chainVerification.errors.join(', ')
      showError.value = true
      return
    }
    
    const { data } = await api.post(
      route('passports.request', { productId: props.product.id })
    )
    
    if (data.success) {
      newPassport.value = data.passport
      showEligibilityDialog.value = false
      showSuccessDialog.value = true
      emit('passport-issued', data.passport)
    } else {
      errorMessage.value = data.error || 'Richiesta passaporto fallita'
      showError.value = true
    }
    
  } catch (e: any) {
    if (e.response?.status === 409) {
      errorMessage.value = 'Esiste già un passaporto per questo prodotto'
    } else if (e.response?.status === 422) {
      const errors = e.response.data?.verification?.errors
      errorMessage.value = 'Prodotto non idoneo: ' + (errors?.join(', ') || 'verifica fallita')
    } else {
      errorMessage.value = e.response?.data?.message || 'Errore durante la richiesta'
    }
    showError.value = true
  } finally {
    requesting.value = false
  }
}

function closeSuccessDialog() {
  showSuccessDialog.value = false
  newPassport.value = null
}
</script>