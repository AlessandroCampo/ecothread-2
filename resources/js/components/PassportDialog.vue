<template>
  <v-dialog v-model="model" max-width="550">
    <v-card>
      <v-card-title class="d-flex align-center bg-success-darken-1 text-white">
        <v-icon class="mr-2">mdi-passport</v-icon>
        Passaporto Digitale
        <v-spacer />
        <v-btn icon="mdi-close" variant="text" density="compact" @click="model = false" />
      </v-card-title>

      <v-card-text class="pa-6">
        <!-- Header con numero passaporto -->
        <div class="text-center mb-6">
          <div class="text-overline text-medium-emphasis">Certificato N°</div>
          <div class="text-h5 font-weight-bold text-success">
            {{ passport?.passport_number }}
          </div>
          <div class="text-caption text-medium-emphasis mt-1">
            Rilasciato il {{ formatDate(passport?.verified_at) }}
          </div>
        </div>

        <!-- QR Code Preview -->
        <v-card variant="outlined" class="mb-6">
          <v-card-text class="text-center pa-4">
            <div class="text-overline text-medium-emphasis mb-3">QR Code di Verifica</div>
            <div ref="qrContainer" class="qr-container mx-auto mb-3">
              <canvas ref="qrCanvas" class="qr-canvas"></canvas>
            </div>
            <div class="text-caption text-medium-emphasis">
              Scansiona per verificare il prodotto
            </div>
          </v-card-text>
        </v-card>

        <!-- Link pubblico -->
        <v-text-field
          :model-value="verificationUrl"
          label="Link di verifica"
          readonly
          variant="outlined"
          density="compact"
          class="mb-4"
        >
          <template #append-inner>
            <v-btn
              icon="mdi-content-copy"
              size="small"
              variant="text"
              @click="copyLink"
            />
          </template>
        </v-text-field>

        <!-- Azioni download -->
        <div class="text-overline text-medium-emphasis mb-3">Scarica</div>
        
        <v-row dense>
          <v-col cols="6">
            <v-btn
              block
              variant="outlined"
              color="primary"
              @click="downloadQR"
            >
              <v-icon start>mdi-qrcode</v-icon>
              QR Code
            </v-btn>
          </v-col>
          <v-col cols="6">
            <v-btn
              block
              variant="outlined"
              color="primary"
              @click="downloadBadge"
            >
              <v-icon start>mdi-certificate</v-icon>
              Badge
            </v-btn>
          </v-col>
        </v-row>

        <!-- Preview Badge -->
        <v-expand-transition>
          <div v-if="showBadgePreview" class="mt-4">
            <div class="text-overline text-medium-emphasis mb-2">Anteprima Badge</div>
            <v-card variant="outlined">
              <canvas ref="badgeCanvas" class="badge-canvas w-100"></canvas>
            </v-card>
          </div>
        </v-expand-transition>
      </v-card-text>

      <v-card-actions class="pa-4 pt-0 d-flex flex-column flex-md-row justify-md-end ga-2">
  <v-btn
    variant="flat"
    @click="showBadgePreview = !showBadgePreview"
    :block="mobile"
    color="secondary"
  >
    {{ showBadgePreview ? 'Nascondi' : 'Mostra' }} anteprima badge
  </v-btn>
  <v-btn
    color="primary"
    variant="flat"
    :href="verificationUrl"
    target="_blank"
    :block="mobile"
  >
    <v-icon start>mdi-open-in-new</v-icon>
    Apri Pagina
  </v-btn>
</v-card-actions>
    </v-card>
    <!---->

    <!-- Snackbar conferma copia -->
    <v-snackbar v-model="showCopySnackbar" :timeout="2000" color="success">
      Link copiato negli appunti!
    </v-snackbar>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import QRCode from 'qrcode'
import { route } from 'ziggy-js'
import { useDisplay } from 'vuetify/lib/composables/display.mjs'
import { downloadCanvas as downloadCanvasFile } from '@/lib/downloadFile'
const {mobile} = useDisplay();
// ============================================
// Types
// ============================================
interface Passport {
  id: number
  passport_number: string
  status: string
  verified_at: string
}

interface Product {
  id: string
  name: string
  product_type?: string
  passport?: Passport | null
}

// ============================================
// Props & Model
// ============================================
const props = defineProps<{
  product: Product
}>()

const model = defineModel<boolean>({ default: false })

// ============================================
// State
// ============================================
const qrCanvas = ref<HTMLCanvasElement | null>(null)
const badgeCanvas = ref<HTMLCanvasElement | null>(null)
const logoLoaded = ref(false)
const logoSrc = '/images/ecothread-logo.png' // Assicurati che esista
const showBadgePreview = ref(false)
const showCopySnackbar = ref(false)

// ============================================
// Computed
// ============================================
const passport = computed(() => props.product.passport)

const verificationUrl = computed(() => {
  if (passport.value?.passport_number) {
    return `${route('passport.verify', { 
      passportNumber: passport.value.passport_number 
    })}`
  }
  return ''
})

// ============================================
// QR Code Generation
// ============================================
// Funzione riutilizzabile per generare QR con logo
async function generateQRWithLogo(
  url: string, 
  size: number = 200
): Promise<HTMLCanvasElement> {
  const canvas = document.createElement('canvas')
  canvas.width = size
  canvas.height = size

  await QRCode.toCanvas(canvas, url, {
    width: size,
    margin: 2,
    color: {
      dark: '#748C70',
      light: '#FFFFFF',
    },
    errorCorrectionLevel: 'H',
  })

  const ctx = canvas.getContext('2d')
  if (!ctx) return canvas

  // Carica logo
  try {
    const logo = await loadImage('/logo-mobile.png')
    
    const centerX = size / 2
    const centerY = size / 2
    const circleRadius = size * 0.14
    const borderWidth = 3
    const padding = 14
    const logoSize = (circleRadius - padding) * 2

    // Sfondo bianco circolare
    ctx.beginPath()
    ctx.arc(centerX, centerY, circleRadius + borderWidth, 0, Math.PI * 2)
    ctx.fillStyle = '#FFFFFF'
    ctx.fill()

    // Bordo circolare
    ctx.beginPath()
    ctx.arc(centerX, centerY, circleRadius, 0, Math.PI * 2)
    ctx.strokeStyle = '#748C70'
    ctx.lineWidth = borderWidth
    ctx.stroke()

    // Logo centrato
    ctx.drawImage(
      logo,
      centerX - logoSize / 2,
      centerY - logoSize / 2,
      logoSize,
      logoSize
    )
  } catch (e) {
    console.warn('Logo non caricato')
  }

  return canvas
}

// Helper per caricare immagini
function loadImage(src: string): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = () => resolve(img)
    img.onerror = reject
    img.src = src
  })
}

// Genera QR nel canvas del componente
async function generateQR() {
  if (!qrCanvas.value || !verificationUrl.value) return

  try {
    const qrWithLogo = await generateQRWithLogo(verificationUrl.value, 200)
    
    const ctx = qrCanvas.value.getContext('2d')
    if (!ctx) return
    
    qrCanvas.value.width = qrWithLogo.width
    qrCanvas.value.height = qrWithLogo.height
    ctx.drawImage(qrWithLogo, 0, 0)
    
    logoLoaded.value = true
  } catch (error) {
    console.error('Errore generazione QR:', error)
  }
}

// Genera badge completo
async function generateBadge(): Promise<HTMLCanvasElement> {
  const canvas = document.createElement('canvas')
  const ctx = canvas.getContext('2d')!
  
  const width = 800
  const height = 1000
  canvas.width = width
  canvas.height = height

  const primary = '#748C70'
  const primaryLight = '#E8F0E7'
  const textDark = '#212121'
  const textMuted = '#757575'

  // Background
  ctx.fillStyle = '#FFFFFF'
  ctx.fillRect(0, 0, width, height)

  // Border
  ctx.strokeStyle = primary
  ctx.lineWidth = 8
  ctx.strokeRect(20, 20, width - 40, height - 40)

  // Header background
  ctx.fillStyle = primary
  ctx.fillRect(20, 20, width - 40, 120)

  // Header text
  ctx.fillStyle = '#FFFFFF'
  ctx.font = 'bold 36px Montserrat, Arial, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText('🌿 ECOTHREAD VERIFIED', width / 2, 95)

  // Passport number
  ctx.fillStyle = primary
  ctx.font = 'bold 28px Montserrat, Arial, sans-serif'
  ctx.fillText(passport.value?.passport_number || '', width / 2, 190)

  // Divider
  ctx.strokeStyle = primaryLight
  ctx.lineWidth = 2
  ctx.beginPath()
  ctx.moveTo(100, 220)
  ctx.lineTo(width - 100, 220)
  ctx.stroke()

  // QR Code con logo (riusa la stessa funzione)
  const qrSize = 280
  const qrX = (width - qrSize) / 2
  const qrY = 260
  
  const qrCanvas = await generateQRWithLogo(verificationUrl.value, qrSize)
  ctx.drawImage(qrCanvas, qrX, qrY)

  // Product name
  ctx.fillStyle = textDark
  ctx.font = 'bold 32px Montserrat, Arial, sans-serif'
  ctx.fillText(props.product.name || 'Prodotto', width / 2, 600)

  // Product type
  if (props.product.product_type) {
    ctx.fillStyle = textMuted
    ctx.font = '24px Montserrat, Arial, sans-serif'
    ctx.fillText(props.product.product_type, width / 2, 640)
  }

  // Divider
  ctx.strokeStyle = primaryLight
  ctx.beginPath()
  ctx.moveTo(100, 680)
  ctx.lineTo(width - 100, 680)
  ctx.stroke()

  // Checklist
  const checkmarks = [
    '✓ Origine verificata',
    '✓ Produzione tracciata', 
    '✓ Trasporto documentato',
    '✓ Impatto dichiarato',
  ]
  
  ctx.fillStyle = primary
  ctx.font = '22px Montserrat, Arial, sans-serif'
  ctx.textAlign = 'left'
  
  checkmarks.forEach((text, i) => {
    ctx.fillText(text, 200, 730 + (i * 35))
  })

  // Footer
  ctx.textAlign = 'center'
  ctx.fillStyle = textMuted
  ctx.font = '18px Montserrat, Arial, sans-serif'
  
  const releaseDate = formatDate(passport.value?.verified_at)
  ctx.fillText(`Rilasciato: ${releaseDate}`, width / 2, 900)
  
  ctx.fillStyle = primary
  ctx.font = '16px Montserrat, Arial, sans-serif'
  ctx.fillText('⛓ Dati immutabili su Solana Blockchain', width / 2, 930)
  
  ctx.fillStyle = textMuted
  ctx.font = '14px Montserrat, Arial, sans-serif'
  ctx.fillText(verificationUrl.value, width / 2, 960)

  return canvas
}
// ============================================
// Download Functions
// ============================================
async function downloadQR() {
  if (!verificationUrl.value) return

  const qrCanvas = await generateQRWithLogo(verificationUrl.value, 1024)
  await downloadCanvasFile(qrCanvas, `qr-${passport.value?.passport_number}.png`)
}

async function downloadBadge() {
  const canvas = await generateBadge()
  await downloadCanvasFile(canvas, `badge-${passport.value?.passport_number}.png`)
}

// ============================================
// Utility Functions
// ============================================
function formatDate(dateString?: string): string {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('it-IT', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

async function copyLink() {
  try {
    await navigator.clipboard.writeText(verificationUrl.value)
    showCopySnackbar.value = true
  } catch (error) {
    console.error('Errore copia:', error)
  }
}

// ============================================
// Watchers
// ============================================
watch(model, async (isOpen) => {
  if (isOpen) {
    await nextTick()
    generateQR()
  }
})

watch(showBadgePreview, async (show) => {
  if (show) {
    await nextTick()
    const canvas = await generateBadge()
    if (badgeCanvas.value) {
      const ctx = badgeCanvas.value.getContext('2d')!
      badgeCanvas.value.width = canvas.width
      badgeCanvas.value.height = canvas.height
      ctx.drawImage(canvas, 0, 0)
    }
  }
})
</script>

<style scoped>
.qr-container {
  position: relative;
  width: 200px;
  height: 200px;
}

.qr-canvas {
  width: 100%;
  height: 100%;
}

.qr-logo {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 50px;
  height: 50px;
  background: white;
  padding: 4px;
  border-radius: 8px;
}

.badge-canvas {
  display: block;
  max-width: 100%;
  height: auto;
}
</style>
