<template>
  <v-card class="mx-4" variant="outlined">
    <!-- Event Header -->
    <v-card-title class="d-flex align-center pb-0">
      <div>
        <div class="text-overline text-medium-emphasis">{{ eventTypeLabel }}</div>
        <div class="text-caption text-medium-emphasis">
          <v-icon size="x-small" class="mr-1">mdi-calendar</v-icon>
          {{ formatTimestamp(event.timestamp) }}
        </div>
      </div>
      <v-spacer />
      <v-chip
        v-if="event.verified"
        color="success"
        size="small"
        variant="tonal"
      >
        <v-icon start size="small">mdi-check-circle</v-icon>
        On-Chain
      </v-chip>
    </v-card-title>

    <v-card-text>
      <!-- Location if present -->
      <div v-if="event.location" class="d-flex align-center mb-3">
        <v-icon size="small" color="grey" class="mr-2">mdi-map-marker</v-icon>
        <span class="text-body-2">{{ event.location }}</span>
      </div>

      <!-- Title / Main claim -->
      <div v-if="event.title" class="text-h6 text-success mb-2">
        {{ event.title }}
      </div>

      <!-- Description -->
      <p v-if="event.description" class="text-body-2 text-medium-emphasis mb-4">
        {{ event.description }}
      </p>

      <!-- Trust Level Badge -->
      <v-chip
        v-if="trustLevel"
        :color="trustLevel.color"
        size="small"
        variant="tonal"
        class="mb-4"
      >
        <v-icon start size="small">{{ trustLevel.icon }}</v-icon>
        {{ trustLevel.label }}
      </v-chip>

      <!-- Hash Display -->
      <div v-if="displayHash" class="hash-container mb-4">
        <div class="d-flex align-center">
          <span class="hash-label">HASH</span>
          <code class="hash-value">{{ truncateHash(displayHash) }}</code>
          <v-btn
            icon="mdi-content-copy"
            size="x-small"
            variant="text"
            @click="copyToClipboard(displayHash)"
          />
        </div>
      </div>

      <!-- Documents Section -->
      <v-expansion-panels v-if="hasDocuments" variant="accordion">
        <v-expansion-panel>
          <v-expansion-panel-title>
            <v-icon color="success" size="small" class="mr-2">mdi-check</v-icon>
            Evidenze Collegate
            <template #actions="{ expanded }">
              <v-icon :icon="expanded ? 'mdi-minus' : 'mdi-plus'" />
            </template>
          </v-expansion-panel-title>
          
          <v-expansion-panel-text>
            <!-- Document Card -->
            <v-card variant="tonal" color="grey-lighten-4" class="mb-3">
              <v-card-text class="pa-3">
                <div class="d-flex align-start">
                  <v-icon color="grey" class="mr-3 mt-1">mdi-file-document</v-icon>
                  <div class="flex-grow-1 min-width-0">
                    <div class="text-subtitle-2">{{ event.document_name || 'Documento' }}</div>
                    <div class="text-caption text-medium-emphasis text-truncate">
                      {{ displayUri }}
                    </div>
                    <div class="text-caption text-medium-emphasis mt-1">
                      Depositato il {{ formatTimestamp(event.timestamp) }}
                    </div>
                    <div class="text-caption font-mono mt-1">
                      Hash: {{ truncateHash(displayHash, 20) }}...
                    </div>
                  </div>
                </div>

                <!-- Verification Button -->
                <v-btn
                  block
                  variant="outlined"
                  color="success"
                  class="mt-3"
                  @click="$emit('verify-document', event)"
                >
                  <v-icon start>mdi-shield-check</v-icon>
                  Verifica Documento
                </v-btn>
              </v-card-text>
            </v-card>


            <!-- Document Preview -->
            <div v-if="documentPreviewUrl" class="document-preview mb-3">
              <v-img
                v-if="isImage"
                :src="documentPreviewUrl"
                max-height="200"
                class="rounded"
                cover
              />
              <v-card
                v-else-if="isPdf"
                variant="outlined"
                class="pa-4 text-center cursor-pointer"
                @click="openDocument"
              >
              <iframe :src="documentPreviewUrl" class="w-100" style="min-height: 500px; overflow-x: hidden;"></iframe>
                <div class="d-flex ga-2 align-center">

                  <v-icon size="48" color="red">mdi-file-pdf-box</v-icon>
                  <div class="text-caption mt-2">Clicca per aprire il documento</div>
                </div>
              </v-card>
              <v-btn
                v-else
                variant="outlined"
                block
                @click="openDocument"
              >
                <v-icon start>mdi-open-in-new</v-icon>
                Apri documento
              </v-btn>
            </div>

            <!-- Copy Hash Action -->
            <v-btn
              variant="text"
              size="small"
              color="primary"
              @click="copyToClipboard(displayHash, 'Hash copiato!')"
            >
              <v-icon start size="small">mdi-content-copy</v-icon>
              Copia e verifica l'hash
            </v-btn>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>

      <!-- Blockchain Link -->
      <div v-if="event.pda_address" class="mt-4">
        <div class="text-caption text-medium-emphasis mb-2">
          <v-icon size="x-small" class="mr-1">mdi-link</v-icon>
          Link Blockchain
        </div>
        <div class="d-flex align-center">
          <code class="hash-value flex-grow-1">{{ truncateHash(event.pda_address, 12) }}</code>
          <v-btn
            icon="mdi-content-copy"
            size="x-small"
            variant="text"
            class="mr-1"
            @click="copyToClipboard(event.pda_address, 'Indirizzo copiato!')"
          />
          <SolanaExplorerLink
            :pda-address="event.pda_address"
            size="x-small"
          />
        </div>
      </div>

      <!-- On-Chain Data Comparison -->
      <v-alert
        v-if="onChainData"
        type="info"
        variant="tonal"
        density="compact"
        class="mt-4"
      >
        <div class="text-caption font-weight-medium mb-1">
          <v-icon size="x-small" class="mr-1">mdi-cube-outline</v-icon>
          Dati diretti dalla Blockchain
        </div>
        <div class="text-caption font-mono">
          Tipo: {{ onChainData.eventType }}<br>
          Hash: {{ truncateHash(onChainData.documentHash, 16) }}...<br>
          Registrante: {{ truncateHash(onChainData.registrant, 8) }}
        </div>
      </v-alert>
    </v-card-text>

    <!-- Snackbar -->
    <v-snackbar v-model="showSnackbar" :timeout="2000" color="success">
      {{ snackbarMessage }}
    </v-snackbar>
  </v-card>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useEnums } from '@/composables/useEnum'
import SolanaExplorerLink from '@/components/SolanaExplorerLink.vue'
import { Event, OnChainEvent } from '@/types';

// ============================================
// Types
// ============================================


// ============================================
// Props & Emits
// ============================================
const props = defineProps<{
  event: Event
  onChainData?: OnChainEvent | null
}>()

defineEmits<{
  (e: 'verify-document', event: Event): void
}>()

// ============================================
// Composables
// ============================================
const { eventTypes, trustLevels } = useEnums()

// ============================================
// State
// ============================================
const showSnackbar = ref(false)
const snackbarMessage = ref('')

// ============================================
// Computed
// ============================================
const eventTypeLabel = computed(() => {
  const enumItem = eventTypes.value.find(t => t.value === props.event.event_type)
  return enumItem?.label || props.event.event_type
})

const trustLevel = computed(() => {
  if (!props.event.trust_level) return null
  return trustLevels.value.find(t => t.value === props.event.trust_level) || null
})

const displayHash = computed(() => {
  return props.onChainData?.documentHash || props.event.document_hash
})

const displayUri = computed(() => {
  return props.onChainData?.documentUri || props.event.document_uri
})

const hasDocuments = computed(() => {
  return !!(displayHash.value || displayUri.value)
})

// Usa direttamente l'URL dal backend
const documentPreviewUrl = computed(() => {
  return props.event.document_gateway_url || null
})

const isImage = computed(() => {
  const mime = props.event.document_mime_type || ''
  return mime.startsWith('image/')
})

const isPdf = computed(() => {
  return props.event.document_mime_type === 'application/pdf'
})

// ============================================
// Methods
// ============================================
function truncateHash(hash?: string, length = 8): string {
  if (!hash) return '-'
  if (hash.length <= length * 2) return hash
  return `${hash.slice(0, length)}...${hash.slice(-length)}`
}

function formatTimestamp(timestamp?: number): string {
  if (!timestamp) return '-'
  
  // Handle both seconds and milliseconds
  const ts = timestamp > 9999999999 ? timestamp : timestamp * 1000
  
  return new Date(ts).toLocaleDateString('it-IT', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

async function copyToClipboard(text?: string, message = 'Copiato!') {
  if (!text) return
  
  try {
    await navigator.clipboard.writeText(text)
    snackbarMessage.value = message
    showSnackbar.value = true
  } catch (e) {
    console.error('Copy failed:', e)
  }
}

function openDocument() {
  if (documentPreviewUrl.value) {
    window.open(documentPreviewUrl.value, '_blank')
  }
}
</script>

<style scoped>
.hash-container {
  background: #F5F5F5;
  border-radius: 8px;
  padding: 8px 12px;
}

.hash-label {
  background: #E0E0E0;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  margin-right: 8px;
}

.hash-value {
  font-family: 'Roboto Mono', monospace;
  font-size: 12px;
  color: #424242;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
}

.document-preview {
  border-radius: 8px;
  overflow: hidden;
}

.font-mono {
  font-family: 'Roboto Mono', monospace;
}

.min-width-0 {
  min-width: 0;
}

.cursor-pointer {
  cursor: pointer;
}
</style>