<template>
  <v-card>
    <v-card-title class="d-flex align-center">
      <v-icon class="mr-2">{{ isEditMode ? 'mdi-pencil' : 'mdi-package-variant-plus' }}</v-icon>
      {{ isEditMode ? 'Modifica ' + product?.id || 'Prodotto' : 'Nuovo Prodotto' }}
      <v-spacer />
      
      <!-- Indicatore wallet solo in create mode -->
      <template v-if="!isEditMode">
        <v-chip 
          v-if="isWalletConnected" 
          color="success" 
          size="small"
          variant="tonal"
        >
          <v-icon start size="small">mdi-wallet</v-icon>
          Wallet Connesso
        </v-chip>
        <v-chip 
          v-else 
          color="warning" 
          size="small"
          variant="tonal"
        >
          <v-icon start size="small">mdi-wallet-outline</v-icon>
          Wallet Disconnesso
        </v-chip>
      </template>
    </v-card-title>

    <v-card-text>
      <!-- Alert: Wallet non connesso (solo create mode) -->
      <wallet-alert v-if="!isEditMode" />

      <!-- Product ID -->
      <v-text-field
        v-model="form.id"
        label="Product ID*"
        :hint="isEditMode ? 'ID registrato su blockchain (non modificabile)' : 'Identificativo univoco registrato su Solana. Non sarà modificabile.'"
        persistent-hint
        :error-messages="errors.id"
        :disabled="isEditMode || !isWalletConnected"
        :readonly="isEditMode"
        variant="outlined"
        class="mb-2"
      >
        <template #prepend-inner>
          <solana-icon />
        </template>
      </v-text-field>

  
      <!-- Nome commerciale -->
      <v-text-field
        v-model="form.name"
        label="Nome commerciale*"
        persistent-hint
        class="mb-3"
        :error-messages="errors.name"
        :disabled="!isEditMode && !isWalletConnected"
        prepend-inner-icon="mdi-tag-text"
      />

      <!-- Descrizione -->
      <v-textarea
        v-model="form.description"
        label="Descrizione"
        rows="2"
        variant="outlined"
        class="mb-3"
        :disabled="!isEditMode && !isWalletConnected"
      />

      <!-- URL prodotto -->
      <v-text-field
        v-model="form.url"
        label="URL E-commerce"
        placeholder="https://esempio.com/prodotto"
        variant="outlined"
        prepend-inner-icon="mdi-link"
        class="mb-3"
        :error-messages="errors.url"
        :disabled="!isEditMode && !isWalletConnected"
      />

      <!-- Product Type -->
      <v-select
        v-model="form.product_type"
        :items="groupedProductTypes"
        item-title="label"
        item-value="value"
        label="Tipo Prodotto*"
        variant="outlined"
        class="mb-3"
        :error-messages="errors.product_type"
        :disabled="!isEditMode && !isWalletConnected"
      >
        <template #item="{ item, props }">
          <v-list-subheader v-if="item.raw.header">
            {{ item.raw.header }}
          </v-list-subheader>
          <v-list-item v-else v-bind="props">
            <template #prepend>
              <span class="mr-2">{{ item.raw.icon }}</span>
            </template>
          </v-list-item>
        </template>

        <template #selection="{ item }">
          <span v-if="item.raw.icon" class="mr-2">{{ item.raw.icon }}</span>
          {{ item.raw.label }}
        </template>
      </v-select>

      <!-- Collection Year -->
      <v-select
        v-model="form.collection_year"
        :items="collectionYears"
        label="Anno Collezione"
        variant="outlined"
        class="mb-3"
        :error-messages="errors.collection_year"
        :disabled="!isEditMode && !isWalletConnected"
      />

      <!-- Immagine prodotto -->
      <v-file-input
        v-model="form.image"
        label="Immagine prodotto"
        variant="outlined"
        prepend-icon="mdi-image"
        accept="image/*"
        class="mb-3"
        :disabled="!isEditMode && !isWalletConnected"
        @update:model-value="onImageChange"
      />

   

      <!-- Image Preview -->
      <v-img
        v-if="imagePreview"
        :src="imagePreview"
        max-height="200"
        class="mb-3 rounded"
        cover
      />

      <v-divider class="my-4" />

      <!-- Progress durante transazione (solo create mode) -->
      <v-expand-transition>
        <div v-if="transactionStep && !isEditMode">
          <v-alert 
            :type="stepAlertType" 
            variant="tonal"
            class="mb-4"
          >
            <template #prepend>
              <v-progress-circular 
                v-if="isStepLoading"
                indeterminate 
                size="20" 
                width="2"
              />
              <v-icon v-else-if="transactionStep === 'success'" color="success">
                mdi-check-circle
              </v-icon>
              <v-icon v-else-if="transactionStep === 'error'" color="error">
                mdi-alert-circle
              </v-icon>
            </template>
            <div class="text-body-2">
              <span v-if="transactionStep === 'saving_draft'">
                💾 Salvataggio dati nel database...
              </span>
              <span v-else-if="transactionStep === 'signing'">
                🔐 Firma la transazione nel tuo wallet...
              </span>
              <span v-else-if="transactionStep === 'confirming'">
                ⏳ Attendo conferma dalla blockchain...
              </span>
              <span v-else-if="transactionStep === 'finalizing'">
                ✨ Finalizzazione del prodotto...
              </span>
              <span v-else-if="transactionStep === 'rolling_back'">
                🔄 Rollback in corso...
              </span>
              <span v-else-if="transactionStep === 'success'">
                ✅ Prodotto creato con successo!
              </span>
              <span v-else-if="transactionStep === 'error'">
                ❌ {{ error }}
              </span>
            </div>
          </v-alert>
        </div>
      </v-expand-transition>

            <v-alert
   color="info" 
        variant="tonal" 
        density="compact"
        class="mb-5"
        v-if="!isEditMode"

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
    <strong>Il Product ID sarà registrato permanentemente su blockchain</strong> 
          come prova della creazione. Tutti gli altri dati (nome, descrizione, immagine...) 
          potranno essere modificati in seguito.
  </div>
</v-alert>

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

    <v-card-actions class="d-flex flex-column flex-md-row justify-md-end ga-2">
  <v-btn 
    @click="$emit('cancel')" 
    variant="flat" 
    color="error" 
    :disabled="loading"
    :block="mobile"
  >
    Annulla
  </v-btn>
  <v-btn
    color="primary"
    :loading="loading"
    :disabled="!isFormValid"
    @click="submit"
    variant="flat"
    :width="mobile ? undefined : 200"
    :block="mobile"
  >
    {{ isEditMode ? 'Salva Modifiche' : 'Crea Prodotto' }}
    <template v-if="!isEditMode" #append>
      <SolanaIcon />
    </template>
  </v-btn>
</v-card-actions>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import api from '@/lib/axios'
import { useSolana } from '@/composables/useSolana'
import { useEnums } from '@/composables/useEnum'
import type { ProductType } from '@/composables/useEnum'
import WalletAlert from './WalletAlert.vue'
import SolanaIcon from './SolanaIcon.vue'
import { useSnack } from '@/composables/useSnack'
import { useDisplay } from 'vuetify/lib/composables/display.mjs'

// ============================================
// Types
// ============================================
interface Product {
  id: string
  name: string
  description?: string
  url?: string
  product_type: string
  collection_year: number
  image_path?: string
}

const {mobile} = useDisplay();

// ============================================
// Props & Emits
// ============================================
const props = defineProps<{
  product?: Product | null
  productTypes?: ProductType[]
}>()

const emit = defineEmits<{
  cancel: []
  created: [product: Product]
  updated: [product: Product]
}>()

// ============================================
// Composables
// ============================================
const { 
  createProduct: createProductOnChain, 
  isWalletConnected,
  getAddressExplorerUrl,
} = useSolana()

const { success: showSuccess, error: showError } = useSnack()
const { productTypes } = useEnums()

const confirmedImmutability = ref(false);
// ============================================
// State
// ============================================
const currentYear = new Date().getFullYear()
const error = ref<string | null>(null)
const errors = ref<Record<string, string[]>>({})
const loading = ref(false)
const imagePreview = ref<string | null>(null)
const transactionStep = ref<string | null>(null)
const draftProductId = ref<string | null>(null)

const form = ref({
  id: '',
  name: '',
  description: '',
  url: '',
  product_type: '',
  collection_year: currentYear,
  image: null as File | null,
})

const result = ref<{
  txSignature: string
  pdaAddress: string
} | null>(null)

// ============================================
// Computed
// ============================================
const isEditMode = computed(() => !!props.product)

const isFormValid = computed(() => {
  if (isEditMode.value) {
    return !!form.value.name && !!form.value.product_type 
  }
  return !!form.value.id && !!form.value.name && !!form.value.product_type && isWalletConnected.value && confirmedImmutability.value
})

const collectionYears = computed(() => {
  const years: number[] = []
  for (let y = currentYear + 2; y >= currentYear - 7; y--) {
    years.push(y)
  }
  return years
})

const stepAlertType = computed(() => {
  if (transactionStep.value === 'error') return 'error'
  if (transactionStep.value === 'success') return 'success'
  if (transactionStep.value === 'rolling_back') return 'warning'
  return 'info'
})

const isStepLoading = computed(() => {
  return transactionStep.value !== null && !['success', 'error'].includes(transactionStep.value)
})

const groupedProductTypes = computed(() => {
  const types = props.productTypes?.length ? props.productTypes : productTypes.value
  return buildGroupedTypes(types)
})

// ============================================
// Watch - Populate form in edit mode
// ============================================
watch(() => props.product, (product) => {
  if (product) {
    form.value = {
      id: product.id,
      name: product.name,
      description: product.description || '',
      url: product.url || '',
      product_type: product.product_type,
      collection_year: product.collection_year,
      image: null,
    }
    imagePreview.value = product.image_path || null
  }
}, { immediate: true })

// ============================================
// Methods
// ============================================
function buildGroupedTypes(types: ProductType[]) {
  const groups: Record<string, string[]> = {
    'Abbigliamento': ['TSHIRT', 'SHIRT', 'PANTS', 'JACKET', 'DRESS', 'SWEATER'],
    'Accessori': ['BAG', 'SHOES', 'SCARF'],
    'Tessile Casa': ['BEDDING', 'TOWEL'],
    'Altro': ['OTHER'],
  }

  const items: Array<ProductType | { header: string; value: string; disabled: boolean }> = []
  
  for (const [header, typeValues] of Object.entries(groups)) {
    items.push({ header, value: `header-${header}`, disabled: true })
    
    for (const typeValue of typeValues) {
      const found = types.find(t => t.value === typeValue)
      if (found) {
        items.push(found)
      }
    }
  }
  return items
}

function onImageChange(file: File | null) {
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  } else {
    imagePreview.value = null
  }
}

async function rollbackDraft(productId: string) {
  try {
    transactionStep.value = 'rolling_back'
    await api.delete(`/admin/products/${productId}`)
  } catch (e) {
    console.error('Rollback failed:', e)
  }
}

async function submit() {
  if (loading.value) return
  
  if (isEditMode.value) {
    await submitUpdate()
  } else {
    await submitCreate()
  }
}

async function submitUpdate() {
  loading.value = true
  error.value = null
  errors.value = {}

  try {
    const formData = new FormData()
    formData.append('name', form.value.name)
    formData.append('description', form.value.description || '')
    formData.append('url', form.value.url || '')
    formData.append('product_type', form.value.product_type)
    formData.append('collection_year', String(form.value.collection_year))

    if (form.value.image) {
      formData.append('image', form.value.image)
    }

    const { data } = await api.post(`/admin/products/${form.value.id}`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    showSuccess('Prodotto aggiornato!')
    emit('updated', data.product)

  } catch (e: any) {
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      error.value = 'Correggi gli errori nel form'
    } else {
      error.value = e.response?.data?.message || e.message || 'Errore durante l\'aggiornamento'
      showError(error.value!)
    }
  } finally {
    loading.value = false
  }
}

async function submitCreate() {
  if (!isWalletConnected.value) return

  loading.value = true
  error.value = null
  errors.value = {}
  result.value = null
  transactionStep.value = null
  draftProductId.value = null

  try {
    // STEP 1: Salvataggio Draft
    transactionStep.value = 'saving_draft'

    const formData = new FormData()
    formData.append('id', form.value.id)
    formData.append('name', form.value.name)
    formData.append('description', form.value.description || '')
    formData.append('url', form.value.url || '')
    formData.append('product_type', form.value.product_type)
    formData.append('collection_year', String(form.value.collection_year))

    if (form.value.image) {
      formData.append('image', form.value.image)
    }

    const { data: draftData } = await api.post('/admin/products', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    draftProductId.value = draftData.product.id

    // STEP 2: Scrittura On-Chain
    transactionStep.value = 'signing'

    const onChainResult = await createProductOnChain(form.value.id)

    if (!onChainResult.success) {
      await rollbackDraft(draftProductId.value!)
      transactionStep.value = 'error'
      error.value = onChainResult.error || 'Errore nella transazione blockchain'
      loading.value = false
      return
    }

    // STEP 3: Conferma Database
    transactionStep.value = 'confirming'
    await new Promise(resolve => setTimeout(resolve, 500))

    transactionStep.value = 'finalizing'

    const { data: confirmedData } = await api.patch(
      `/admin/products/${draftProductId.value}/confirm`,
      {
        pda_address: onChainResult.pdaAddress,
        tx_signature: onChainResult.txSignature,
        creation_timestamp: Math.floor(Date.now() / 1000),
      }
    )

    // STEP 4: Success
    transactionStep.value = 'success'
    
    result.value = {
      txSignature: onChainResult.txSignature!,
      pdaAddress: onChainResult.pdaAddress!,
    }

    showSuccess(`Prodotto ${form.value.id} creato on-chain!`, {
      text: 'Vedi su Solana',
      href: getAddressExplorerUrl(onChainResult.pdaAddress!),
    })

    setTimeout(() => {
      emit('created', confirmedData.product)
    }, 500)

  } catch (e: any) {
    if (draftProductId.value && transactionStep.value !== 'saving_draft') {
      await rollbackDraft(draftProductId.value)
    }

    transactionStep.value = 'error'
    
    if (e.response?.status === 422) {
      errors.value = e.response.data.errors || {}
      error.value = 'Correggi gli errori nel form'
    } else {
      error.value = e.response?.data?.message || e.message || 'Errore durante la creazione'
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
  font-size: 0.85em;
}
</style>