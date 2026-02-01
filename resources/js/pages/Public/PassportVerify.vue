<!-- resources/js/Pages/Public/PassportVerify.vue -->
<template>
  <v-app>
    <div class="passport-verify">
      <!-- Header -->
      <header class="header bg-primary-lighten-5">
        <div class="header-content">
          <a href="/" class="logo">
            <v-img width="200" src="/logo.png" class="d-none d-sm-block"
            style="margin-left: -30px"
            />
            <v-img width="40" src="/logo-mobile.png" class="d-block d-sm-none"

            />
          </a>
          <v-btn
            icon="mdi-share-variant"
            variant="text"
            size="small"
            @click="sharePassport"
          />
        </div>
      </header>

      <!-- Loading state -->
      <div v-if="loading" class="loading-container">
        <v-progress-circular indeterminate color="primary" size="48" />
        <p class="mt-4 text-body-2 text-medium-emphasis">
          Verifico i dati sulla blockchain...
        </p>
      </div>

      <!-- Error state -->
      <v-alert
        v-else-if="error"
        type="error"
        variant="tonal"
        class="ma-4"
        rounded="xl"
      >
        <v-alert-title>Errore di verifica</v-alert-title>
        {{ error }}
      </v-alert>

      <!-- Main content -->
      <main v-else class="main-content">
        <!-- Product Card -->
        <v-card class="product-card mx-4 mt-4" variant="flat" rounded="xl">
          <div class="d-flex">
            <div class="product-image-wrapper">
              <v-img
                v-if="product.image_url"
                :src="product.image_url"
                aspect-ratio="1"
                cover
                class="product-image"
              />
              <div v-else class="product-image-placeholder">
                <v-icon size="48" color="grey-lighten-1">mdi-tshirt-crew</v-icon>
              </div>
            </div>
            
            <div class="product-details pa-4">
              <h1 class="text-h6 font-weight-bold mb-1">{{ product.name }}</h1>
              
              <!-- Company -->
              <div v-if="product.company?.name" class="d-flex align-center ga-2 mb-2">
                <v-avatar size="20" class="company-avatar">
                  <v-img v-if="product.company.logo_url" :src="product.company.logo_url" />
                  <v-icon v-else size="12" color="primary">mdi-domain</v-icon>
                </v-avatar>
                <span class="text-body-2 text-medium-emphasis">{{ product.company.name }}</span>
              </div>
              
              <div class="text-caption text-medium-emphasis mb-3">
                Anno {{ product.collection_year || new Date().getFullYear() }}
              </div>

              <!-- Verified Badge -->
              <v-card 
                variant="tonal" 
                color="success" 
                class="verified-badge pa-2 rounded-lg"
                density="compact"
              >
                <div class="d-flex align-start ga-2">
                  <v-icon size="18" color="success">mdi-check-decagram</v-icon>
                  <div>
                    <div class="text-caption font-weight-bold">Dati verificabili</div>
                    <div class="text-caption" style="font-size: 10px; line-height: 1.3;">
                      Informazioni registrate su blockchain
                    </div>
                  </div>
                </div>
              </v-card>
            </div>
          </div>
        </v-card>

        <!-- Timeline Section -->
        <v-card class="timeline-card mx-4 my-4" variant="flat" rounded="xl">
          <v-card-text class="pa-4">
            <div class="d-flex align-center ga-2 mb-4">
              <v-icon color="primary" size="20">mdi-check-decagram</v-icon>
              <span class="text-subtitle-2 font-weight-bold">Timeline del Prodotto</span>
            </div>

        <v-timeline 
  direction="horizontal" 
  line-color="grey-lighten-2"
  density="compact"
  class="timeline-horizontal mb-4"
>
  <v-timeline-item
    v-for="(event, index) in sortedEvents"
    :key="event.id"
    :dot-color="selectedEventIndex === index ? 'primary' : (event.is_on_chain ? 'primary-lighten-3' : 'grey-lighten-2')"
    class="cursor-pointer"
    @click="selectedEventIndex = index"
  >
    <template #icon>
      <v-icon 
        :size="16"
        :color="selectedEventIndex === index ? 'white' : (event.is_on_chain ? 'primary' : 'grey')"
      >
        {{ getEventIcon(event.event_type) }}
      </v-icon>
    </template>

    <!-- Slot default, non opposite -->
    <div 
      class="timeline-label"
      :class="{ 
        'text-primary font-weight-bold': selectedEventIndex === index,
        'text-medium-emphasis': selectedEventIndex !== index
      }"
    >
      {{ getEventShortLabel(event.event_type) }}
      <v-icon 
        v-if="event.is_on_chain" 
        size="14" 
        color="success"
      >
        mdi-check-circle
      </v-icon>
    </div>
  </v-timeline-item>
</v-timeline>

            <!-- Selected Event -->
            <v-window v-model="selectedEventIndex">
              <v-window-item
                v-for="(event, index) in sortedEvents"
                :key="event.id"
                :value="index"
                class="pa-4"
              >
                <EventCard 
                  :event="event" 
                  :default-expanded="true"
                  :show-blockchain-info="true"
                />
              </v-window-item>
            </v-window>
          </v-card-text>
        </v-card>

        <!-- Passport Info Card -->
        <v-card class="passport-info-card mx-4 mt-4" variant="flat" rounded="xl">
          <v-card-text class="pa-4">
            <div class="d-flex align-center ga-2 mb-3">
              <v-icon color="primary" size="20">mdi-passport</v-icon>
              <span class="text-subtitle-2 font-weight-bold">Passaporto Digitale</span>
            </div>
            
            <div class="info-grid">
              <div class="info-item">
                <span class="info-label">Numero</span>
                <span class="info-value font-weight-medium">{{ passport?.passport_number }}</span>
              </div>
              <div class="info-item">
                <span class="info-label">Rilasciato</span>
                <span class="info-value">{{ formatDate(passport?.verified_at) }}</span>
              </div>
              <div class="info-item info-item--full">
                <span class="info-label">Registrato da</span>
                <span class="info-value font-mono text-truncate">
                  {{ truncateAddress(product.creator_wallet) }}
                </span>
              </div>
            </div>

            <!-- Blockchain Verification -->
            <div class="blockchain-status mt-4 pa-3 rounded-lg" :class="blockchainVerified ? 'bg-success-subtle' : 'bg-warning-subtle'">
              <div class="d-flex align-center ga-2">
                <v-icon :color="blockchainVerified ? 'success' : 'warning'" size="20">
                  {{ blockchainVerified ? 'mdi-shield-check' : 'mdi-shield-alert' }}
                </v-icon>
                <div class="flex-grow-1">
                  <div class="text-body-2 font-weight-medium">
                    {{ blockchainVerified ? 'Verificato su Solana' : 'Verifica in corso...' }}
                  </div>
                  <div class="text-caption text-medium-emphasis">
                    {{ blockchainVerified ? 'Dati immutabili e trasparenti' : 'Connessione alla blockchain...' }}
                  </div>
                </div>
                <v-btn
                  v-if="product.pda_address"
                  size="x-small"
                  variant="text"
                  color="primary"
                  :href="explorerUrl"
                  target="_blank"
                >
                  <v-icon size="16">mdi-open-in-new</v-icon>
                </v-btn>
              </div>
            </div>
          </v-card-text>
        </v-card>

        <!-- Disclaimer -->
        <v-card class="disclaimer-card mx-4 mt-4 mb-6" variant="tonal" color="grey-lighten-3" rounded="xl">
          <v-card-text class="pa-3 text-caption text-medium-emphasis">
            <v-icon size="14" class="mr-1">mdi-information</v-icon>
            <strong>Nota:</strong> I dati sono dichiarati dal brand e registrati su blockchain. 
            EcoThread certifica la trasparenza e l'immutabilità delle informazioni, 
            non la veridicità delle dichiarazioni.
          </v-card-text>
        </v-card>

        <!-- Footer -->
        <div class="footer text-center pb-6">
          <a href="/" class="text-decoration-none">
            <v-img src="/logo.png" width="100" class="mx-auto mb-2" />
          </a>
          <div class="text-caption text-medium-emphasis">
            Powered by Solana Blockchain
          </div>
        </div>
      </main>

      <!-- Share Dialog -->
      <v-dialog v-model="showShareDialog" max-width="400">
        <v-card rounded="xl">
          <v-card-title class="text-center pt-6">Condividi</v-card-title>
          <v-card-text class="text-center">
            <canvas ref="shareQrCanvas" class="mb-4" />
            <v-text-field
              :model-value="shareUrl"
              readonly
              variant="outlined"
              density="compact"
              append-inner-icon="mdi-content-copy"
              @click:append-inner="copyShareUrl"
            />
          </v-card-text>
          <v-card-actions class="justify-center pb-6">
            <v-btn variant="text" @click="showShareDialog = false">Chiudi</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Snackbar -->
      <v-snackbar v-model="showSnackbar" :timeout="2000" color="success">
        {{ snackbarMessage }}
      </v-snackbar>
    </div>
  </v-app>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useSolana } from '@/composables/useSolana'
import EventCard from '@/components/EventCard.vue'
import QRCode from 'qrcode'
import type { Event, Product, Passport, OnChainEvent } from '@/types'

// Props
const props = defineProps<{
  product: Product
  passport: Passport
  events: Event[]
}>()

// Composables
const { fetchProduct, fetchEvent, whenReady } = useSolana()

// State
const loading = ref(true)
const error = ref<string | null>(null)
const selectedEventIndex = ref(0)
const blockchainVerified = ref(false)
const onChainProduct = ref<any>(null)
const onChainEvents = ref<Record<number, OnChainEvent>>({})

// Share
const showShareDialog = ref(false)
const shareQrCanvas = ref<HTMLCanvasElement | null>(null)
const showSnackbar = ref(false)
const snackbarMessage = ref('')

// Computed
const sortedEvents = computed(() => {
  return [...(props.events || [])].sort((a, b) => (a.index ?? 0) - (b.index ?? 0))
})

const shareUrl = computed(() => {
  return window.location.href
})

const explorerUrl = computed(() => {
  if (!props.product.pda_address) return '#'
  return `https://explorer.solana.com/address/${props.product.pda_address}?cluster=devnet`
})

// Methods
function getEventIcon(type: string): string {
  const icons: Record<string, string> = {
    'ORIGIN': 'mdi-sprout',
    'PRODUCTION': 'mdi-factory',
    'TRANSPORT': 'mdi-truck',
    'PACKAGING': 'mdi-package-variant',
    'RECYCLE': 'mdi-recycle',
    'CERTIFICATION': 'mdi-certificate',
    'ENV_CLAIM': 'mdi-leaf',
    'CUSTOM': 'mdi-tag',
  }
  return icons[type] || 'mdi-calendar'
}

function getEventShortLabel(type: string): string {
  const labels: Record<string, string> = {
    'ORIGIN': 'Origine',
    'PRODUCTION': 'Produzione',
    'TRANSPORT': 'Trasporto',
    'PACKAGING': 'Packaging',
    'RECYCLE': 'Riciclo',
    'CERTIFICATION': 'Certificazione',
    'ENV_CLAIM': 'Impatto',
    'CUSTOM': 'Altro',
  }
  return labels[type] || type
}

function formatDate(dateString?: string): string {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('it-IT', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  })
}

function truncateAddress(address?: string): string {
  if (!address) return '-'
  return `${address.slice(0, 6)}...${address.slice(-4)}`
}

async function sharePassport() {
  showShareDialog.value = true
  
  // Generate QR
  if (shareQrCanvas.value) {
    await QRCode.toCanvas(shareQrCanvas.value, shareUrl.value, {
      width: 200,
      margin: 2,
      color: {
        dark: '#748C70',
        light: '#FFFFFF',
      },
    })
  }
}

async function copyShareUrl() {
  try {
    await navigator.clipboard.writeText(shareUrl.value)
    snackbarMessage.value = 'Link copiato!'
    showSnackbar.value = true
  } catch (e) {
    console.error('Copy failed:', e)
  }
}

async function verifyOnBlockchain() {
  loading.value = true
  error.value = null

  try {
    await new Promise(resolve => setTimeout(resolve, 300))
    
    if (props.product.pda_address) {
      const productResult = await fetchProduct(props.product.id)
      if (productResult.success) {
        onChainProduct.value = productResult.data
        
        const numEvents = productResult.data.eventCount || 0
        
        for (let i = 0; i < numEvents; i++) {
          const dbEvent = props.events?.find(e => e.index === i)
          
          if (dbEvent?.pda_address) {
            try {
              const eventResult = await fetchEvent(dbEvent.pda_address)
              if (eventResult.success && eventResult.data) {
                onChainEvents.value[eventResult.data.eventIndex] = eventResult.data
              }
            } catch (e) {
              console.warn(`Failed to fetch event index ${i}:`, e)
            }
          }
        }
      }
    }

    blockchainVerified.value = !!onChainProduct.value && Object.keys(onChainEvents.value).length > 0
  } catch (e: any) {
    error.value = e.message || 'Error during blockchain verification'
    console.error(e)
  } finally {
    loading.value = false
  }
}

// Lifecycle
onMounted(async () => {
  try {
    await whenReady()
    await verifyOnBlockchain()
  } catch (e: any) {
    error.value = e.message
    loading.value = false
  }
})
</script>

<style scoped>
.passport-verify {
  min-height: 100vh;
  background: linear-gradient(180deg, #F8FAF8 0%, #FFFFFF 100%);
}

.header {
  background: white;
  border-bottom: 1px solid #E8EBE8;
  position: sticky;
  top: 0;
  z-index: 100;
}

.header-content {
 max-width: min(100vw, 700px);
  margin: 0 auto;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-height: 70px;

}

.logo {
  display: flex;
  align-items: center;
}

.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
}

.main-content {
  max-width: min(100vw, 700px);
  margin: 0 auto;
}

/* Product Card */
.product-card {
  background: white;
  overflow: hidden;
}

.product-image-wrapper {
  width: 120px;
  flex-shrink: 0;
}

.product-image {
  height: 100%;
  min-height: 160px;
}

.product-image-placeholder {
  height: 100%;
  min-height: 160px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #F5F7F5;
}

.product-details {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.company-avatar {
  border: 1.5px solid rgb(var(--v-theme-primary));
  background: white;
}

.verified-badge {
  margin-top: auto;
}

.timeline-horizontal {
  overflow-x: auto;
}

.timeline-horizontal :deep(.v-timeline-item) {
  flex: 1 1 0;
  min-width: 60px;
  max-width: 100px;
}

.timeline-horizontal :deep(.v-timeline-item__opposite) {
  padding: 4px 0 !important;
  align-self: center;
}

.timeline-horizontal :deep(.v-timeline-item__body) {
  margin-top: -15px;
}

.timeline-label {
  font-size: 11px;
  white-space: nowrap;
  text-align: center;
}

.cursor-pointer {
  cursor: pointer;
}ursor-pointer {
  cursor: pointer;
}
/* Passport Info */
.passport-info-card {
  background: white;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.info-item--full {
  grid-column: 1 / -1;
}

.info-label {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: rgba(0, 0, 0, 0.5);
}

.info-value {
  font-size: 14px;
}

.blockchain-status {
  transition: all 0.3s ease;
}

.bg-success-subtle {
  background: rgba(var(--v-theme-success), 0.08);
}

.bg-warning-subtle {
  background: rgba(var(--v-theme-warning), 0.08);
}

/* Disclaimer */
.disclaimer-card {
  background: #F8F8F8;
}

/* Footer */
.footer {
  padding-top: 24px;
}

/* Utils */
.font-mono {
  font-family: 'Roboto Mono', monospace;
  font-size: 12px;
}
</style>