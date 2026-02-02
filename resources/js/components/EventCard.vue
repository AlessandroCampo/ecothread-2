<!-- resources/js/componentsEventCard.vue -->
<template>
  <v-card 
    :variant="'flat'"
    :elevation="expanded ? 2 : 0"
    class="event-card"
    :class="{ 'event-card--expanded': expanded }"
  >
    <!-- Header cliccabile -->
    <div 
      class="event-header pa-4 d-flex align-center cursor-pointer"
      @click="expanded = !expanded"
    >
      <v-avatar 
        :color="eventTypeInfo.color" 
        size="40" 
        class="mr-3"
        variant="tonal"
      >
        <v-icon size="20">{{ eventTypeInfo.icon }}</v-icon>
      </v-avatar>
      
      <div class="flex-grow-1">
        <div class="d-flex align-center ga-2">
          <span class="text-subtitle-1 font-weight-bold">{{ eventTypeInfo.label }}</span>
          <v-icon 
            v-if="event.is_on_chain" 
            size="16" 
            color="success"
            v-tooltip="'Verificato on-chain'"
          >
            mdi-check-decagram
          </v-icon>
        </div>
        <div class="text-caption text-medium-emphasis">
          {{ formatDate(event.timestamp || event.created_at) }}
        </div>
      </div>
      
      <v-chip 
        v-if="!expanded"
        :color="trustLevelInfo.color" 
        size="x-small" 
        variant="tonal"
        class="mr-2"
      >
        {{ trustLevelInfo.label }}
      </v-chip>
      
      <v-icon :class="{ 'rotate-180': expanded }">
        mdi-chevron-down
      </v-icon>
    </div>

    <!-- Contenuto espandibile -->
    <v-expand-transition>
      <div v-show="expanded">
        <v-divider />
        
        <v-card-text class="pa-4">
          <!-- Trust Level Badge -->
          <v-chip 
            :color="trustLevelInfo.color" 
            size="small" 
            variant="tonal"
            class="mb-4"
          >
            <v-icon start size="x-small">{{ trustLevelInfo.icon }}</v-icon>
            {{ trustLevelInfo.label }}
          </v-chip>

          <!-- Descrizione -->
          <p v-if="event.description" class="text-body-2 mb-4">
            {{ event.description }}
          </p>

          <!-- Metadati specifici per tipo -->
          <div v-if="hasMetadata" class="metadata-section mb-4">
            
            <!-- ORIGIN -->
            <template v-if="event.event_type === 'ORIGIN'">
              <div class="metadata-row">
                <span class="metadata-label">Provenienza</span>
                <span class="metadata-value">
                  <span :class="`fi fi-${event.metadata?.country?.toLowerCase()}`" class="mr-2" />
                  {{ getCountryName(event.metadata?.country) }}
                  <span v-if="event.metadata?.region" class="text-medium-emphasis">
                    ({{ event.metadata.region }})
                  </span>
                </span>
              </div>

              <div v-if="event.metadata?.compositions?.length" class="metadata-row">
                <span class="metadata-label">Composizione</span>
                <div class="d-flex flex-wrap ga-1">
                  <v-chip
                    v-for="(comp, i) in event.metadata.compositions"
                    :key="i"
                    size="small"
                    variant="tonal"
                    color="primary"
                  >
                    {{ getMaterialLabel(comp.material) }} {{ comp.percentage }}%
                  </v-chip>
                </div>
              </div>
            </template>

            <!-- PRODUCTION -->
            <template v-else-if="event.event_type === 'PRODUCTION'">
              <div v-if="event.metadata?.processes?.length" class="metadata-row">
                <span class="metadata-label">Lavorazioni</span>
                <div class="d-flex flex-wrap ga-1">
                  <v-chip
                    v-for="process in event.metadata.processes"
                    :key="process"
                    size="small"
                    variant="tonal"
                    color="blue"
                  >
                    {{ getProcessLabel(process) }}
                  </v-chip>
                </div>
              </div>

              <div v-if="event.metadata?.water_usage_liters" class="metadata-row">
                <span class="metadata-label">Consumo idrico</span>
                <span class="metadata-value">
                  <v-icon size="16" class="mr-1" color="blue">mdi-water</v-icon>
                  {{ event.metadata.water_usage_liters }} L/capo
                </span>
              </div>

              <div v-if="event.metadata?.energy_kwh" class="metadata-row">
                <span class="metadata-label">Consumo energetico</span>
                <span class="metadata-value">
                  <v-icon size="16" class="mr-1" color="amber">mdi-lightning-bolt</v-icon>
                  {{ event.metadata.energy_kwh }} kWh/capo
                </span>
              </div>
            </template>

            <!-- TRANSPORT -->
            <template v-else-if="event.event_type === 'TRANSPORT'">
              <div class="metadata-row">
                <span class="metadata-label">Tratta</span>
                <span class="metadata-value">
                  <span :class="`fi fi-${event.metadata?.origin_country?.toLowerCase()}`" class="mr-1" />
                  {{ getCountryName(event.metadata?.origin_country) }}
                  <v-icon size="14" class="mx-2">mdi-arrow-right</v-icon>
                  <span :class="`fi fi-${event.metadata?.destination_country?.toLowerCase()}`" class="mr-1" />
                  {{ getCountryName(event.metadata?.destination_country) }}
                </span>
              </div>

              <div class="metadata-row">
                <span class="metadata-label">Modalità</span>
                <span class="metadata-value">
                  <v-icon size="16" class="mr-1">{{ getTransportIcon(event.metadata?.transport_mode) }}</v-icon>
                  {{ getTransportLabel(event.metadata?.transport_mode) }}
                </span>
              </div>

              <div v-if="event.metadata?.distance_km" class="metadata-row">
                <span class="metadata-label">Distanza</span>
                <span class="metadata-value">{{ event.metadata.distance_km }} km</span>
              </div>

              <div v-if="event.metadata?.co2_kg" class="metadata-row">
                <span class="metadata-label">Emissioni CO₂</span>
                <span class="metadata-value">{{ event.metadata.co2_kg }} kg</span>
              </div>
            </template>

            <!-- PACKAGING -->
            <template v-else-if="event.event_type === 'PACKAGING'">
              <div v-if="event.metadata?.materials?.length" class="metadata-row">
                <span class="metadata-label">Materiali</span>
                <div class="d-flex flex-wrap ga-1">
                  <v-chip
                    v-for="material in event.metadata.materials"
                    :key="material"
                    size="small"
                    variant="tonal"
                    color="brown"
                  >
                    {{ getPackagingLabel(material) }}
                  </v-chip>
                </div>
              </div>

              <div class="metadata-row">
                <span class="metadata-label">Riciclabile</span>
                <v-chip 
                  :color="event.metadata?.is_recyclable ? 'success' : 'grey'" 
                  size="small"
                  variant="tonal"
                >
                  <v-icon start size="14">
                    {{ event.metadata?.is_recyclable ? 'mdi-recycle' : 'mdi-close' }}
                  </v-icon>
                  {{ event.metadata?.is_recyclable ? 'Sì' : 'No' }}
                </v-chip>
              </div>
            </template>

            <!-- RECYCLE -->
            <template v-else-if="event.event_type === 'RECYCLE'">
              <div class="metadata-row">
                <span class="metadata-label">Riciclabilità</span>
                <div class="d-flex align-center ga-2">
                  <v-progress-linear
                    :model-value="event.metadata?.recycle_percentage"
                    color="success"
                    height="8"
                    rounded
                    style="max-width: 120px"
                  />
                  <span class="text-body-2 font-weight-medium">
                    {{ event.metadata?.recycle_percentage }}%
                  </span>
                </div>
              </div>

              <div class="metadata-row">
                <span class="metadata-label">Programma ritiro</span>
                <v-chip 
                  :color="event.metadata?.take_back_program ? 'success' : 'grey'" 
                  size="small"
                  variant="tonal"
                >
                  {{ event.metadata?.take_back_program ? 'Attivo' : 'Non disponibile' }}
                </v-chip>
              </div>
            </template>

            <!-- CERTIFICATION -->
            <template v-else-if="event.event_type === 'CERTIFICATION'">
              <div class="metadata-row">
                <span class="metadata-label">Certificazione</span>
                <span class="metadata-value font-weight-medium">
                  {{ getCertificationLabel(event.metadata?.certification_type) }}
                </span>
              </div>

              <div class="metadata-row">
                <span class="metadata-label">Ente certificatore</span>
                <span class="metadata-value">{{ event.metadata?.issued_by }}</span>
              </div>

              <div v-if="event.metadata?.valid_until" class="metadata-row">
                <span class="metadata-label">Scadenza</span>
                <span class="metadata-value">{{ formatDate(event.metadata.valid_until) }}</span>
              </div>

              <div v-if="event.metadata?.certificate_number" class="metadata-row">
                <span class="metadata-label">N° certificato</span>
                <span class="metadata-value font-mono">{{ event.metadata.certificate_number }}</span>
              </div>
            </template>

            <!-- ENV_CLAIM / CUSTOM -->
            <template v-else-if="event.event_type === 'ENV_CLAIM' || event.event_type === 'CUSTOM'">
              <v-alert type="info" variant="tonal" density="compact" class="mb-0">
                Dichiarazione ambientale basata sui dati della filiera registrati.
              </v-alert>
            </template>
          </div>

          <!-- Verifica Metadati Button & Result -->
          <div v-if="showBlockchainInfo && event.is_on_chain && hasMetadata" class="verification-section mb-4">
            <v-btn
              size="small"
              variant="tonal"
              color="primary"
              :loading="verifyingMetadata"
              @click="verifyMetadata"
              :disabled="!blockchainVerified"
              block
            >
              <v-icon start size="16">mdi-database-check</v-icon>
              Verifica dati
            </v-btn>

            <v-expand-transition>
              <v-alert
                v-if="metadataVerification"
                :type="metadataVerification.valid ? 'success' : 'error'"
                variant="tonal"
                density="compact"
                class="mt-3"
                closable
                @click:close="metadataVerification = null"
              >
                <div class="font-weight-medium">
                  {{ metadataVerification.valid ? 'Dati verificati' : 'Dati alterati!' }}
                </div>
                <div class="text-caption mt-1">
                  {{ metadataVerification.valid 
                    ? 'I metadati corrispondono a quelli registrati su blockchain' 
                    : 'I metadati nel database non corrispondono all\'hash registrato su blockchain' 
                  }}
                </div>
                
                <div class="hash-comparison mt-3">
                  <div class="hash-row">
                    <span class="hash-row-label">On-chain:</span>
                    <code class="hash-row-value" :class="{ 'text-success': metadataVerification.valid }">
                      {{ truncateHash(metadataVerification.onChainHash, 12) || 'N/D' }}
                    </code>
                  </div>
                  <div class="hash-row">
                    <span class="hash-row-label">Calcolato:</span>
                    <code class="hash-row-value" :class="{ 'text-error': !metadataVerification.valid }">
                      {{ truncateHash(metadataVerification.calculatedHash, 12) }}
                    </code>
                  </div>
                  <v-icon 
                    :color="metadataVerification.valid ? 'success' : 'error'" 
                    size="20"
                    class="hash-match-icon"
                  >
                    {{ metadataVerification.valid ? 'mdi-check-circle' : 'mdi-close-circle' }}
                  </v-icon>
                </div>
              </v-alert>
            </v-expand-transition>
          </div>

          <!-- Documento allegato -->
          <v-card 
            v-if="event.document_name || event.document_hash" 
            variant="tonal" 
            color="secondary" 
            class="mb-4"
          >
            <v-card-text class="pa-3">
              <div class="d-flex align-center mb-2">
                <v-icon color="primary" size="20" class="mr-2">mdi-file-document</v-icon>
                <span class="text-subtitle-2">Documentazione allegata</span>
              </div>
              
              <div class="text-body-2 mb-2">{{ event.document_name || 'Documento' }}</div>
              
              <!-- Document Preview -->
              <div v-if="event.document_gateway_url" class="document-preview mb-3">
                <v-img
                  v-if="isImage"
                  :src="event.document_gateway_url"
                  max-height="150"
                  class="rounded"
                  cover
                />
                <iframe 
                  v-else-if="isPdf"
                  :src="event.document_gateway_url" 
                  class="w-100 rounded" 
                  style="height: 200px; border: none;"
                />
              </div>

              <!-- Hash documento -->
              <div v-if="event.document_hash" class="hash-display mb-3">
                <span class="hash-label">HASH DOC</span>
                <code class="hash-value">{{ truncateHash(event.document_hash) }}</code>
                <v-btn
                  icon="mdi-content-copy"
                  size="x-small"
                  variant="text"
                  @click="copyToClipboard(event.document_hash)"
                />
              </div>

              <!-- Actions -->
              <div class="d-flex ga-2 flex-wrap">
                <v-btn
                  v-if="event.document_gateway_url"
                  size="small"
                  variant="outlined"
                  color="primary"
                  :href="event.document_gateway_url"
                  target="_blank"
                >
                  <v-icon start size="16">mdi-open-in-new</v-icon>
                  Apri
                </v-btn>
                <v-btn
                  v-if="showBlockchainInfo && event.is_on_chain && event.document_hash"
                  size="small"
                  variant="tonal"
                  color="success"
                  :loading="verifyingDocument"
                  @click="verifyDocument"
                  :disabled="!blockchainVerified"
                >
                  <v-icon start size="16">mdi-file-check</v-icon>
                  Verifica documento
                </v-btn>
              </div>

              <!-- Document Verification Result -->
              <v-expand-transition>
                <v-alert
                  v-if="documentVerification"
                  :type="documentVerification.valid ? 'success' : 'error'"
                  variant="tonal"
                  density="compact"
                  class="mt-3"
                  closable
                  @click:close="documentVerification = null"
                >
                  <div class="font-weight-medium">
                    {{ documentVerification.valid ? 'Documento verificato' : 'Documento alterato!' }}
                  </div>
                  <div class="text-caption mt-1">
                    {{ documentVerification.valid 
                      ? 'L\'hash del documento corrisponde a quello registrato su blockchain' 
                      : 'L\'hash del documento non corrisponde a quello registrato su blockchain' 
                    }}
                  </div>
                  
                  <div class="hash-comparison mt-3">
                    <div class="hash-row">
                      <span class="hash-row-label">On-chain:</span>
                      <code class="hash-row-value" :class="{ 'text-success': documentVerification.valid }">
                        {{ truncateHash(documentVerification.onChainHash, 12) || 'N/D' }}
                      </code>
                    </div>
                    <div class="hash-row">
                      <span class="hash-row-label">Database:</span>
                      <code class="hash-row-value" :class="{ 'text-error': !documentVerification.valid }">
                        {{ truncateHash(documentVerification.storedHash, 12) }}
                      </code>
                    </div>
                    <v-icon 
                      :color="documentVerification.valid ? 'success' : 'error'" 
                      size="20"
                      class="hash-match-icon"
                    >
                      {{ documentVerification.valid ? 'mdi-check-circle' : 'mdi-close-circle' }}
                    </v-icon>
                  </div>
                </v-alert>
              </v-expand-transition>
            </v-card-text>
          </v-card>

          <!-- Blockchain info -->
          <div v-if="showBlockchainInfo && event.is_on_chain" class="blockchain-info">
            <div class="d-flex align-center justify-space-between">
              <div class="d-flex align-center text-caption text-medium-emphasis">
                <v-icon size="14" color="success" class="mr-1">mdi-check-decagram</v-icon>
                Registrato on-chain
              </div>
              <v-btn
                v-if="event.pda_address"
                size="x-small"
                variant="text"
                color="primary"
                :href="explorerUrl"
                target="_blank"
              >
                Vedi su Solana
                <v-icon end size="12">mdi-open-in-new</v-icon>
              </v-btn>
            </div>
          </div>
        </v-card-text>
      </div>
    </v-expand-transition>

    <!-- Snackbar -->
    <v-snackbar v-model="showSnackbar" :timeout="2000" color="success">
      {{ snackbarMessage }}
    </v-snackbar>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useEnums } from '@/composables/useEnum'
import { useSolana } from '@/composables/useSolana'
import CountryList from 'country-list-with-dial-code-and-flag'
import 'flag-icons/css/flag-icons.min.css'

interface EventData {
  id: number
  event_type: string
  trust_level: string
  description?: string
  metadata?: Record<string, any>
  document_name?: string
  document_path?: string
  document_hash?: string
  document_mime_type?: string
  document_gateway_url?: string
  is_on_chain: boolean
  pda_address?: string
  tx_signature?: string
  timestamp?: number
  created_at?: string
  index?: number
}

interface MetadataVerificationResult {
  valid: boolean
  onChainHash: string | null
  calculatedHash: string
}

interface DocumentVerificationResult {
  valid: boolean
  onChainHash: string | null
  storedHash: string
}

const props = withDefaults(defineProps<{
  event: EventData
  showBlockchainInfo?: boolean
  isCompany?: boolean
  defaultExpanded?: boolean,
  blockchainVerified?: boolean,
}>(), {
  showBlockchainInfo: true,
  isCompany: false,
  defaultExpanded: false,
  blockchainVerified: true

})

// Composables
const { eventTypes, trustLevels, eventEnums, loadEventEnums } = useEnums()
const { fetchEvent, hashMetadata } = useSolana()

// State
const expanded = ref(props.defaultExpanded)
const showSnackbar = ref(false)
const snackbarMessage = ref('')

// Verification states
const verifyingMetadata = ref(false)
const verifyingDocument = ref(false)
const metadataVerification = ref<MetadataVerificationResult | null>(null)
const documentVerification = ref<DocumentVerificationResult | null>(null)

onMounted(() => {
  loadEventEnums()
})

// Computed
const hasMetadata = computed(() => {
  return props.event.metadata && Object.keys(props.event.metadata).length > 0
})

const eventTypeInfo = computed(() => {
  const found = eventTypes.value.find(e => e.value === props.event.event_type)
  return found || { label: props.event.event_type, icon: 'mdi-calendar', color: 'grey' }
})

const trustLevelInfo = computed(() => {
  const found = trustLevels.value.find(t => t.value === props.event.trust_level)
  return found || { label: props.event.trust_level, icon: 'mdi-help', color: 'grey' }
})

const explorerUrl = computed(() => {
  if (!props.event.pda_address) return '#'
  return `https://explorer.solana.com/address/${props.event.pda_address}?cluster=devnet`
})

const isImage = computed(() => {
  const mime = props.event.document_mime_type || ''
  return mime.startsWith('image/')
})

const isPdf = computed(() => {
  return props.event.document_mime_type === 'application/pdf'
})

// ============================================
// Verifica METADATI
// ============================================
async function verifyMetadata() {
  if (!props.event.pda_address) return

  verifyingMetadata.value = true
  metadataVerification.value = null

  try {
    // 1. Calcola hash dai metadati attuali nel database
    const calculatedHash = await hashMetadata(props.event.metadata || {})

    // 2. Fetch dati da Solana
    const onChainData = await fetchEvent(props.event.pda_address)
    const onChainHash = onChainData?.data?.metadataHash || null

    // 3. Confronta
    const isValid = onChainHash === calculatedHash && onChainHash !== '0'.repeat(64)

    metadataVerification.value = {
      valid: isValid,
      onChainHash,
      calculatedHash,
    }
  } catch (error) {
    console.error('Errore verifica metadati:', error)
    metadataVerification.value = {
      valid: false,
      onChainHash: null,
      calculatedHash: await hashMetadata(props.event.metadata || {}),
    }
  } finally {
    verifyingMetadata.value = false
  }
}

// ============================================
// Verifica DOCUMENTO
// ============================================
async function verifyDocument() {
  if (!props.event.pda_address || !props.event.document_hash) return

  verifyingDocument.value = true
  documentVerification.value = null

  try {
    // 1. Hash documento salvato nel database
    const storedHash = props.event.document_hash

    // 2. Fetch dati da Solana
    const onChainData = await fetchEvent(props.event.pda_address)
    const onChainHash = onChainData?.data?.documentHash || null

    // 3. Confronta
    const isValid = onChainHash === storedHash && onChainHash !== '0'.repeat(64)

    documentVerification.value = {
      valid: isValid,
      onChainHash,
      storedHash,
    }
  } catch (error) {
    console.error('Errore verifica documento:', error)
    documentVerification.value = {
      valid: false,
      onChainHash: null,
      storedHash: props.event.document_hash || '',
    }
  } finally {
    verifyingDocument.value = false
  }
}

// ============================================
// Helpers
// ============================================
function truncateHash(hash?: string | null, length = 8): string {
  if (!hash) return '-'
  if (hash.length <= length * 2) return hash
  return `${hash.slice(0, length)}...${hash.slice(-length)}`
}

function formatDate(dateInput?: number | string): string {
  if (!dateInput) return '-'
  
  let date: Date
  if (typeof dateInput === 'number') {
    date = new Date(dateInput > 9999999999 ? dateInput : dateInput * 1000)
  } else {
    date = new Date(dateInput)
  }
  
  return date.toLocaleDateString('it-IT', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

async function copyToClipboard(text?: string) {
  if (!text) return
  try {
    await navigator.clipboard.writeText(text)
    snackbarMessage.value = 'Copiato!'
    showSnackbar.value = true
  } catch (e) {
    console.error('Copy failed:', e)
  }
}

function getCountryName(code?: string): string {
  if (!code) return ''
  const country = CountryList.findOneByCountryCode(code.toUpperCase())
  return country?.name || code
}

function getMaterialLabel(value?: string): string {
  if (!value) return ''
  const found = eventEnums.value.materials?.find(m => m.value === value)
  return found?.label || value
}

function getProcessLabel(value?: string): string {
  if (!value) return ''
  const found = eventEnums.value.processes?.find(p => p.value === value)
  return found?.label || value
}

function getTransportLabel(value?: string): string {
  if (!value) return ''
  const found = eventEnums.value.transportModes?.find(t => t.value === value)
  return found?.label || value
}

function getTransportIcon(value?: string): string {
  if (!value) return 'mdi-truck'
  const found = eventEnums.value.transportModes?.find(t => t.value === value)
  return found?.icon || 'mdi-truck'
}

function getPackagingLabel(value?: string): string {
  if (!value) return ''
  const found = eventEnums.value.packagingMaterials?.find(p => p.value === value)
  return found?.label || value
}

function getCertificationLabel(value?: string): string {
  if (!value) return ''
  const found = eventEnums.value.certificationTypes?.find(c => c.value === value)
  return found?.label || value
}
</script>

<style scoped>
.event-card {
  transition: all 0.3s ease;
  border-radius: 16px !important;
  overflow: hidden;
}

.event-card--expanded {
  background: white;
}

.event-header {
  transition: background 0.2s ease;
}

.event-header:hover {
  background: rgba(var(--v-theme-primary), 0.04);
}

.cursor-pointer {
  cursor: pointer;
}

.rotate-180 {
  transform: rotate(180deg);
  transition: transform 0.3s ease;
}

.metadata-section {
  background: #FAFAFA;
  border-radius: 12px;
  padding: 16px;
}

.metadata-row {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 8px 0;
  border-bottom: 1px solid rgba(0, 0, 0, 0.06);
}

.metadata-row:last-child {
  border-bottom: none;
}

.metadata-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: rgba(var(--v-theme-on-surface), 0.6);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.metadata-value {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 4px;
}

.verification-section {
  background: rgba(var(--v-theme-primary), 0.04);
  border-radius: 12px;
  padding: 12px;
}

.hash-display {
  background: #F5F5F5;
  border-radius: 8px;
  padding: 8px 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.hash-label {
  background: #E0E0E0;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  white-space: nowrap;
}

.hash-value {
  font-family: 'Roboto Mono', monospace;
  font-size: 11px;
  color: #424242;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
}

.hash-comparison {
  background: rgba(0, 0, 0, 0.04);
  border-radius: 8px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  position: relative;
}

.hash-row {
  display: flex;
  align-items: center;
  gap: 8px;
}

.hash-row-label {
  font-size: 11px;
  color: rgba(0, 0, 0, 0.6);
  min-width: 70px;
}

.hash-row-value {
  font-family: 'Roboto Mono', monospace;
  font-size: 11px;
  word-break: break-all;
  flex: 1;
}

.hash-match-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
}

.blockchain-info {
  padding-top: 12px;
  border-top: 1px solid rgba(0, 0, 0, 0.06);
}

.document-preview {
  border-radius: 8px;
  overflow: hidden;
}

.font-mono {
  font-family: 'Roboto Mono', monospace;
}
</style>