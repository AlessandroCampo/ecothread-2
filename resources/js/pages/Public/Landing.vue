<!-- resources/js/Pages/Public/Landing.vue -->
<template>
    <v-app>

        <div class="landing-page">
          <!-- Navbar -->
          <v-app-bar flat color="white" class="px-4">
            <div class="d-flex align-center">
              <img src="/logo-mobile.png" alt="EcoThread" height="40" class="mr-2" />
              <span class="text-h6 font-weight-bold text-primary">EcoThread</span>
            </div>
            <v-spacer />
            <v-btn 
              variant="outlined" 
              color="primary" 
              class="mr-2"
              :href="route('login')"
            >
              Accedi
            </v-btn>
          </v-app-bar>
      
          <!-- Hero Section -->
          <section class="hero-section">
            <v-container class="py-16">
              <v-row align="center" justify="center">
                <v-col cols="12" md="6" class="text-center text-md-left">
                  <v-chip color="primary" variant="tonal" class="mb-4">
                    <v-icon start size="small">mdi-leaf</v-icon>
                    Powered by Solana Blockchain
                  </v-chip>
                  
                  <h1 class="text-h4 text-md-h3 text-md-h2 font-weight-bold mb-4 hero-title">
                    La verità sulla
                    <span class="text-primary">sostenibilità</span>
                    dei tuoi capi
                  </h1>
                  
                  <p class="text-h6 text-medium-emphasis mb-8 hero-subtitle">
                    EcoThread trasforma le dichiarazioni di sostenibilità in prove verificabili. 
                    Scansiona, verifica, scegli consapevolmente.
                  </p>
                  
                  <div class="d-flex flex-column flex-sm-row ga-4 justify-center justify-md-start">
                    <v-btn
                      size="x-large"
                      color="primary"
                      @click="scrollToScanner"
                    >
                      <v-icon start>mdi-qrcode-scan</v-icon>
                      Verifica un prodotto
                    </v-btn>
                    <v-btn
                      size="x-large"
                      variant="outlined"
                      color="primary"
                      :href="route('login')"
                    >
                      Sei un'azienda?
                    </v-btn>
                  </div>
                </v-col>
                
                <v-col cols="12" md="6" class="d-none d-md-flex justify-center">
                  <div class="hero-illustration">
                    <v-card class="passport-preview elevation-12 rounded-xl pa-6">
                      <div class="d-flex align-center mb-4">
                        <v-icon color="primary" size="32" class="mr-2">mdi-shield-check</v-icon>
                        <span class="text-h6 font-weight-bold">Digital Passport</span>
                      </div>
                      <v-divider class="mb-4" />
                      <div class="d-flex justify-center mb-4">
                        <canvas ref="heroQrCanvas" width="150" height="150"></canvas>
                      </div>
                      <div class="text-center">
                        <div class="text-body-1 font-weight-medium">T-Shirt Organic Cotton</div>
                        <div class="text-body-2 text-medium-emphasis">GreenWear S.r.l.</div>
                      </div>
                      <v-divider class="my-4" />
                      <div class="d-flex flex-wrap ga-2 justify-center">
                        <v-chip size="small" color="primary" variant="tonal">
                          <v-icon start size="x-small">mdi-check</v-icon>
                          Origine
                        </v-chip>
                        <v-chip size="small" color="primary" variant="tonal">
                          <v-icon start size="x-small">mdi-check</v-icon>
                          Produzione
                        </v-chip>
                        <v-chip size="small" color="primary" variant="tonal">
                          <v-icon start size="x-small">mdi-check</v-icon>
                          Trasporto
                        </v-chip>
                      </div>
                    </v-card>
                  </div>
                </v-col>
              </v-row>
            </v-container>
            
            <!-- Wave divider -->
            <div class="wave-divider">
              <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
                <path 
                  fill="#F5F7F5" 
                  d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,120L1360,120C1280,120,1120,120,960,120C800,120,640,120,480,120C320,120,160,120,80,120L0,120Z"
                />
              </svg>
            </div>
          </section>
      
          <!-- Problem Section -->
          <section class="problem-section py-16 bg-light">
            <v-container>
              <v-row justify="center" class="mb-12">
                <v-col cols="12" md="8" class="text-center">
                  <v-chip color="error" variant="tonal" class="mb-4">Il problema</v-chip>
                  <h2 class="text-h4 text-md-h3 font-weight-bold mb-4">
                    Il greenwashing costa caro
                  </h2>
                  <p class="text-h6 text-medium-emphasis">
                    Il 53% delle dichiarazioni ambientali sono vaghe o ingannevoli.
                    I consumatori non hanno strumenti per verificare la verità.
                  </p>
                </v-col>
              </v-row>
              
              <v-row>
                <v-col v-for="problem in problems" :key="problem.title" cols="12" md="4">
                  <v-card variant="flat" class="pa-6 h-100 bg-white rounded-xl">
                    <v-icon :color="problem.color" size="48" class="mb-4">{{ problem.icon }}</v-icon>
                    <h3 class="text-h6 font-weight-bold mb-2">{{ problem.title }}</h3>
                    <p class="text-body-2 text-medium-emphasis">{{ problem.description }}</p>
                  </v-card>
                </v-col>
              </v-row>
            </v-container>
          </section>
      
          <!-- Solution Section -->
          <section class="solution-section py-16">
            <v-container>
              <v-row justify="center" class="mb-12">
                <v-col cols="12" md="8" class="text-center">
                  <v-chip color="primary" variant="tonal" class="mb-4">La soluzione</v-chip>
                  <h2 class="text-h4 text-md-h3 font-weight-bold mb-4">
                    Trasparenza verificabile, non promessa
                  </h2>
                  <p class="text-h6 text-medium-emphasis">
                    EcoThread registra ogni passaggio della filiera su blockchain.
                    I dati sono immutabili, verificabili da chiunque, per sempre.
                  </p>
                </v-col>
              </v-row>
              
              <v-row align="center">
                <v-col cols="12" md="6">
                  <v-timeline side="end" density="comfortable">
                    <v-timeline-item
                      v-for="(step, i) in howItWorks"
                      :key="i"
                      :dot-color="step.color"
                      size="small"
                    >
                      <template #icon>
                        <v-icon size="small" color="white">{{ step.icon }}</v-icon>
                      </template>
                      <v-card variant="flat" class="pa-4 bg-light rounded-lg">
                        <div class="text-overline text-primary mb-1">Step {{ i + 1 }}</div>
                        <h4 class="text-subtitle-1 font-weight-bold mb-1">{{ step.title }}</h4>
                        <p class="text-body-2 text-medium-emphasis mb-0">{{ step.description }}</p>
                      </v-card>
                    </v-timeline-item>
                  </v-timeline>
                </v-col>
                
                <v-col cols="12" md="6" class="d-none d-md-block">
                  <v-img 
                    src="/images/blockchain-illustration.svg" 
                    max-height="400"
                    class="mx-auto"
                  />
                </v-col>
              </v-row>
            </v-container>
          </section>
      
          <!-- Features Section -->
          <section class="features-section py-16 bg-light">
            <v-container>
              <v-row justify="center" class="mb-12">
                <v-col cols="12" md="8" class="text-center">
                  <v-chip color="primary" variant="tonal" class="mb-4">Funzionalità</v-chip>
                  <h2 class="text-h4 text-md-h3 font-weight-bold mb-4">
                    Tutto ciò che serve per verificare
                  </h2>
                </v-col>
              </v-row>
              
              <v-row>
                <v-col v-for="feature in features" :key="feature.title" cols="12" sm="6" md="3">
                  <v-card variant="flat" class="pa-6 h-100 bg-white rounded-xl text-center">
                    <v-avatar color="primary" variant="tonal" size="64" class="mb-4">
                      <v-icon size="32">{{ feature.icon }}</v-icon>
                    </v-avatar>
                    <h3 class="text-subtitle-1 font-weight-bold mb-2">{{ feature.title }}</h3>
                    <p class="text-body-2 text-medium-emphasis mb-0">{{ feature.description }}</p>
                  </v-card>
                </v-col>
              </v-row>
            </v-container>
          </section>
      
          <!-- Scanner Section -->
          <section id="scanner-section" class="scanner-section py-16">
            <v-container>
              <v-row justify="center">
                <v-col cols="12" md="8" lg="6">
                  <v-card class="rounded-xl overflow-hidden">
                    <v-card-title class="bg-primary text-white pa-6">
                      <v-icon class="mr-2">mdi-qrcode-scan</v-icon>
                      Verifica un prodotto
                    </v-card-title>
                    
                    <v-card-text class="pa-6">
                      <v-tabs v-model="scannerTab" color="primary" class="mb-6">
                        <v-tab value="scan">
                          <v-icon start>mdi-camera</v-icon>
                          Scansiona QR
                        </v-tab>
                        <v-tab value="search">
                          <v-icon start>mdi-magnify</v-icon>
                          Cerca prodotto
                        </v-tab>
                      </v-tabs>
                      
                      <v-tabs-window v-model="scannerTab">
                        <!-- QR Scanner -->
                        <v-tabs-window-item value="scan">
                          <div v-if="!cameraActive" class="text-center py-8">
                            <v-icon size="80" color="primary" class="mb-4">mdi-qrcode-scan</v-icon>
                            <p class="text-body-1 mb-4">
                              Scansiona il QR code presente sull'etichetta del prodotto
                            </p>
                            <v-btn color="primary" size="large" @click="startCamera">
                              <v-icon start>mdi-camera</v-icon>
                              Attiva fotocamera
                            </v-btn>
                          </div>
                          
                          <div v-else class="camera-container">
                            <video ref="videoElement" class="camera-video rounded-lg"></video>
                            <div class="camera-overlay">
                              <div class="scan-area"></div>
                            </div>
                            <v-btn 
                              class="mt-4" 
                              variant="outlined" 
                              color="primary" 
                              block
                              @click="stopCamera"
                            >
                              Chiudi fotocamera
                            </v-btn>
                          </div>
                        </v-tabs-window-item>
                        
                        <!-- Search -->
                        <v-tabs-window-item value="search">
                          <v-text-field
                            v-model="searchQuery"
                            label="ID Prodotto o Passport Number"
                            placeholder="Es: ECO-2024-XXXXX"
                            variant="outlined"
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            class="mb-4"
                            @keyup.enter="searchProduct"
                          />
                          
                          <v-btn 
                            color="primary" 
                            size="large" 
                            block
                            :loading="searching"
                            :disabled="!searchQuery"
                            @click="searchProduct"
                          >
                            Cerca prodotto
                          </v-btn>
                          
                          <v-alert 
                            v-if="searchError" 
                            type="error" 
                            variant="tonal" 
                            class="mt-4"
                            closable
                            @click:close="searchError = null"
                          >
                            {{ searchError }}
                          </v-alert>
                        </v-tabs-window-item>
                      </v-tabs-window>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-container>
          </section>
      
          <!-- For Business Section -->
          <section class="business-section py-16 bg-primary">
            <v-container>
              <v-row align="center">
                <v-col cols="12" md="7">
                  <h2 class="text-h4 text-md-h3 font-weight-bold text-white mb-4">
                    Sei un brand di moda?
                  </h2>
                  <p class="text-h6 text-white-darken-1 mb-6">
                    Differenziati dalla concorrenza con trasparenza verificabile.
                    Preparati alla normativa EU Digital Product Passport 2027.
                  </p>
                  
                  <v-list bg-color="transparent" class="mb-6">
                    <v-list-item 
                      v-for="benefit in businessBenefits" 
                      :key="benefit"
                      class="text-white px-0"
                    >
                      <template #prepend>
                        <v-icon color="white" class="mr-3">mdi-check-circle</v-icon>
                      </template>
                      <v-list-item-title class="text-white">{{ benefit }}</v-list-item-title>
                    </v-list-item>
                  </v-list>
              
                </v-col>
                
                <v-col cols="12" md="5" class="d-none d-md-block">
                  <v-card class="rounded-xl pa-6">
                    <div class="text-center mb-4">
                      <v-icon size="64" color="primary">mdi-store</v-icon>
                    </div>
                    <h3 class="text-h5 font-weight-bold text-center mb-4">Dashboard Brand</h3>
                    <v-list density="compact">
                      <v-list-item>
                        <template #prepend>
                          <v-icon color="primary" size="small">mdi-package-variant</v-icon>
                        </template>
                        <v-list-item-title class="text-body-2">Registra prodotti</v-list-item-title>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend>
                          <v-icon color="primary" size="small">mdi-timeline</v-icon>
                        </template>
                        <v-list-item-title class="text-body-2">Traccia la filiera</v-list-item-title>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend>
                          <v-icon color="primary" size="small">mdi-certificate</v-icon>
                        </template>
                        <v-list-item-title class="text-body-2">Carica certificazioni</v-list-item-title>
                      </v-list-item>
                      <v-list-item>
                        <template #prepend>
                          <v-icon color="primary" size="small">mdi-qrcode</v-icon>
                        </template>
                        <v-list-item-title class="text-body-2">Genera QR e badge</v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-card>
                </v-col>
              </v-row>
            </v-container>
          </section>
      
          <!-- Tech Section -->
          <section class="tech-section py-16 bg-light">
            <v-container>
              <v-row justify="center" class="mb-8">
                <v-col cols="12" md="8" class="text-center">
                  <v-chip color="primary" variant="tonal" class="mb-4">Tecnologia</v-chip>
                  <h2 class="text-h4 text-md-h3 font-weight-bold mb-4">
                    Costruito su Solana
                  </h2>
                  <p class="text-h6 text-medium-emphasis">
                    Transazioni veloci, economiche e a basso impatto ambientale
                  </p>
                </v-col>
              </v-row>
              
              <v-row justify="center">
                <v-col v-for="stat in techStats" :key="stat.label" cols="6" md="3">
                  <v-card variant="flat" class="pa-6 text-center bg-white rounded-xl">
                    <div class="text-h4 text-md-h3 font-weight-bold text-primary mb-2">{{ stat.value }}</div>
                    <div class="text-body-2 text-medium-emphasis">{{ stat.label }}</div>
                  </v-card>
                </v-col>
              </v-row>
            </v-container>
          </section>
      
          <!-- Footer -->
          <footer class="footer py-12">
            <v-container>
              <v-row>
                <v-col cols="12" md="4" class="mb-6 mb-md-0">
                  <div class="d-flex align-center mb-4">
                    <img src="/logo-mobile.png" alt="EcoThread" height="40" class="mr-2" />
                    <span class="text-h6 font-weight-bold text-primary">EcoThread</span>
                  </div>
                  <p class="text-body-2 text-medium-emphasis">
                    Trasformiamo le dichiarazioni di sostenibilità in prove verificabili 
                    attraverso la tecnologia blockchain.
                  </p>
                </v-col>
                
                <v-col cols="6" md="2">
                  <h4 class="text-subtitle-2 font-weight-bold mb-4">Prodotto</h4>
                  <v-list density="compact" bg-color="transparent" class="pa-0">
                    <v-list-item class="px-0 min-h-0 mb-2">
                      <a href="#scanner-section" class="text-body-2 text-decoration-none text-medium-emphasis">
                        Verifica prodotto
                      </a>
                    </v-list-item>
                    <v-list-item class="px-0 min-h-0 mb-2">
                      <a :href="route('login')" class="text-body-2 text-decoration-none text-medium-emphasis">
                        Per aziende
                      </a>
                    </v-list-item>
                  </v-list>
                </v-col>
                
                <v-col cols="6" md="2">
                  <h4 class="text-subtitle-2 font-weight-bold mb-4">Risorse</h4>
                  <v-list density="compact" bg-color="transparent" class="pa-0">
                    <v-list-item class="px-0 min-h-0 mb-2">
                      <a href="#" class="text-body-2 text-decoration-none text-medium-emphasis">
                        Come funziona
                      </a>
                    </v-list-item>
                    <v-list-item class="px-0 min-h-0 mb-2">
                      <a href="#" class="text-body-2 text-decoration-none text-medium-emphasis">
                        FAQ
                      </a>
                    </v-list-item>
                  </v-list>
                </v-col>
                
                <v-col cols="12" md="4">
                  <h4 class="text-subtitle-2 font-weight-bold mb-4">Progetto di tesi</h4>
                  <p class="text-body-2 text-medium-emphasis mb-2">
                    EcoThread è un Proof of Concept sviluppato come progetto di tesi 
                    presso l'Università degli Studi Internazionali di Roma.
                  </p>
                  <v-chip size="small" color="primary" variant="tonal">
                    <v-icon start size="x-small">mdi-school</v-icon>
                    Progetto accademico
                  </v-chip>
                </v-col>
              </v-row>
              
              <v-divider class="my-8" />
              
              <v-row align="center">
                <v-col cols="12" md="6" class="text-center text-md-left">
                  <span class="text-body-2 text-medium-emphasis">
                    © {{ new Date().getFullYear() }} EcoThread. Progetto di ricerca.
                  </span>
                </v-col>
                <v-col cols="12" md="6" class="text-center text-md-right">
                  <v-btn 
                    icon 
                    variant="text" 
                    size="small"
                    href="https://explorer.solana.com/?cluster=devnet" 
                    target="_blank"
                  >
                    <v-icon>mdi-link-variant</v-icon>
                    <v-tooltip activator="parent" location="top">Solana Explorer</v-tooltip>
                  </v-btn>
                  <v-btn 
                    icon 
                    variant="text" 
                    size="small"
                    href="https://github.com" 
                    target="_blank"
                  >
                    <v-icon>mdi-github</v-icon>
                    <v-tooltip activator="parent" location="top">GitHub</v-tooltip>
                  </v-btn>
                </v-col>
              </v-row>
            </v-container>
          </footer>
        </div>
    </v-app>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import QRCode from 'qrcode'
import jsQR from 'jsqr'
import { route } from 'ziggy-js'
import api from '@/lib/axios'


// Refs
const heroQrCanvas = ref<HTMLCanvasElement | null>(null)
const videoElement = ref<HTMLVideoElement | null>(null)
const scannerTab = ref('scan')
const cameraActive = ref(false)
const searchQuery = ref('')
const searching = ref(false)
const searchError = ref<string | null>(null)

let mediaStream: MediaStream | null = null
let scanInterval: number | null = null

// Data
const problems = [
  {
    icon: 'mdi-eye-off',
    color: 'error',
    title: 'Opacità della filiera',
    description: 'I consumatori non hanno visibilità su dove e come vengono prodotti i capi.',
  },
  {
    icon: 'mdi-file-document-alert',
    color: 'warning',
    title: 'Dichiarazioni non verificabili',
    description: 'Le certificazioni cartacee possono essere falsificate o modificate.',
  },
  {
    icon: 'mdi-scale-unbalanced',
    color: 'error',
    title: 'Asimmetria informativa',
    description: 'Le aziende controllano quali informazioni condividere e quando.',
  },
]

const howItWorks = [
  {
    icon: 'mdi-package-variant-closed',
    color: '#748C70',
    title: 'Il brand registra il prodotto',
    description: 'Ogni capo viene registrato su blockchain con un ID univoco.',
  },
  {
    icon: 'mdi-timeline-plus',
    color: '#748C70',
    title: 'Aggiunge eventi alla filiera',
    description: 'Origine materie prime, produzione, trasporto, certificazioni.',
  },
  {
    icon: 'mdi-file-certificate',
    color: '#748C70',
    title: 'Carica documenti e prove',
    description: 'Certificazioni, report, dichiarazioni con hash immutabile.',
  },
  {
    icon: 'mdi-qrcode-scan',
    color: '#748C70',
    title: 'Il consumatore verifica',
    description: 'Scansiona il QR e accede a tutta la storia verificabile.',
  },
]

const features = [
  {
    icon: 'mdi-shield-lock',
    title: 'Dati immutabili',
    description: 'Una volta registrati, i dati non possono essere modificati o cancellati.',
  },
  {
    icon: 'mdi-clock-check',
    title: 'Timestamp certificato',
    description: 'Ogni evento ha data e ora verificabile dalla blockchain.',
  },
  {
    icon: 'mdi-file-check',
    title: 'Documenti hashati',
    description: 'Ogni documento ha un\'impronta digitale che ne garantisce l\'integrità.',
  },
  {
    icon: 'mdi-account-check',
    title: 'Firme crittografiche',
    description: 'Ogni dichiarazione è firmata e riconducibile a chi l\'ha fatta.',
  },
]

const businessBenefits = [
  'Differenziazione competitiva attraverso trasparenza verificabile',
  'Compliance anticipata con EU Digital Product Passport (2027)',
  'Riduzione del rischio reputazionale da accuse di greenwashing',
  'Giustificazione premium pricing con prove concrete',
  'Storytelling del prodotto verificabile e coinvolgente',
]

const techStats = [
  { value: '0.5 Wh', label: 'Energia per transazione' },
  { value: '<1s', label: 'Tempo di conferma' },
  { value: '~$0.001', label: 'Costo per evento' },
  { value: '∞', label: 'Durata dei dati' },
]

// Methods
function scrollToScanner() {
  document.getElementById('scanner-section')?.scrollIntoView({ behavior: 'smooth' })
}

async function startCamera() {
  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment' }
    })

    cameraActive.value = true

    // Aspetta che Vue aggiorni il DOM (il video element è in v-if)
    await nextTick()

    if (videoElement.value) {
      videoElement.value.srcObject = mediaStream
      await videoElement.value.play()

      // Start scanning
      scanInterval = window.setInterval(scanQRCode, 200)
    }
  } catch (error) {
    console.error('Camera error:', error)
    searchError.value = 'Impossibile accedere alla fotocamera'
  }
}

function stopCamera() {
  if (mediaStream) {
    mediaStream.getTracks().forEach(track => track.stop())
    mediaStream = null
  }
  if (scanInterval) {
    clearInterval(scanInterval)
    scanInterval = null
  }
  cameraActive.value = false
}

function scanQRCode() {
  if (!videoElement.value || !cameraActive.value) return

  const video = videoElement.value
  if (video.readyState !== video.HAVE_ENOUGH_DATA) return
  
  const canvas = document.createElement('canvas')
  canvas.width = video.videoWidth
  canvas.height = video.videoHeight
  
  const ctx = canvas.getContext('2d')
  if (!ctx) return
  
  ctx.drawImage(video, 0, 0)
  const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
  const code = jsQR(imageData.data, imageData.width, imageData.height)
  
  if (code?.data) {
    stopCamera()
    handleQRResult(code.data)
  }
}

function handleQRResult(data: string) {
  // Controlla se è un URL di EcoThread
  if (data.includes('/passport/') || data.includes('/product/') || data.includes('verify')) {
    window.location.href = data
  } else {
    searchError.value = 'QR code non riconosciuto come prodotto EcoThread'
  }
}

async function searchProduct() {
  if (!searchQuery.value) return
  
  searching.value = true
  searchError.value = null
  
  try {
    // Cerca per passport number o product ID
    const {data} = await api.get(`/api/public/search?q=${encodeURIComponent(searchQuery.value)}`)
    
    if (data.success && data.url) {
      router.visit(data.url)
    } else {
      searchError.value = data.message || 'Prodotto non trovato'
    }
  } catch (error) {
    searchError.value = 'Errore durante la ricerca'
  } finally {
    searching.value = false
  }
}

async function generateHeroQR() {
  if (!heroQrCanvas.value) return
  
  try {
    await QRCode.toCanvas(heroQrCanvas.value, 'https://ecothread.app/demo', {
      width: 150,
      margin: 2,
      color: {
        dark: '#748C70',
        light: '#FFFFFF',
      },
    })
  } catch (error) {
    console.error('QR error:', error)
  }
}

// Lifecycle
onMounted(() => {
  generateHeroQR()
})

onUnmounted(() => {
  stopCamera()
})
</script>

<style scoped>
.landing-page {
  overflow-x: hidden;
}

.hero-section {
  background: linear-gradient(135deg, #FFFFFF 0%, #F5F7F5 100%);
  position: relative;
  min-height: 90vh;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.hero-title {
  line-height: 1.1;
}

.hero-subtitle {
  line-height: 1.6;
}

.wave-divider {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  line-height: 0;
}

.wave-divider svg {
  width: 100%;
  height: 80px;
}

.bg-light {
  background-color: #F5F7F5;
}

.bg-primary {
  background-color: #748C70;
}

.passport-preview {
  max-width: 300px;
  transform: rotate(3deg);
  transition: transform 0.3s ease;
}

.passport-preview:hover {
  transform: rotate(0deg) scale(1.02);
}

.camera-container {
  position: relative;
}

.camera-video {
  width: 100%;
  max-height: 300px;
  object-fit: cover;
  background: #000;
}

.camera-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
}

.scan-area {
  width: 200px;
  height: 200px;
  border: 3px solid #748C70;
  border-radius: 16px;
  box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
}

.footer {
  background-color: #FAFAFA;
}

.text-white-darken-1 {
  color: rgba(255, 255, 255, 0.8) !important;
}
</style>