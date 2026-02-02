<template>
  <v-container class="fill-height d-flex h-screen bg-primary-darken-1" fluid>
    <v-row justify="center" align="center">
      <v-col cols="12" sm="8" md="4">
        <v-card class="pa-6" elevation="8">
          <v-card-title class="text-center text-h4 mb-4 d-flex justify-center">
             <img  src="/logo.png" class="d-none d-md-block" width="300"/>
              <img width="100" src="/logo-mobile.png" class="d-block d-md-none"/>
          </v-card-title>
          
          <v-card-subtitle class="text-center mb-6">
            Accedi con il tuo wallet Solana
          </v-card-subtitle>

          <v-alert v-if="error" type="error" class="mb-4" closable @click:close="error = null">
            {{ error }}
          </v-alert>

          <!-- MOBILE: Deep link a Phantom App -->
          <div v-if="isMobile && !isInPhantomBrowser">
            <v-btn
              color="primary"
              size="large"
              block
              :loading="loading"
              @click="openInPhantomApp"
            >
              <v-icon start>mdi-cellphone</v-icon>
              Apri in Phantom App
            </v-btn>
            
            <v-divider class="my-4">
              <span class="text-caption text-medium-emphasis">oppure</span>
            </v-divider>
            
            <v-btn
              variant="outlined"
              color="primary"
              size="large"
              block
              href="https://phantom.app/download"
              target="_blank"
            >
              <v-icon start>mdi-download</v-icon>
              Installa Phantom
            </v-btn>
            
            <p class="text-caption text-center mt-4 text-medium-emphasis">
              Su mobile, EcoThread funziona all'interno del browser di Phantom
            </p>
          </div>

          <!-- DESKTOP / IN-APP BROWSER: Flusso normale -->
          <div v-else>
            <!-- Stato: Wallet non connesso -->
            <v-btn
              v-if="!connected"
              color="primary"
              size="large"
              block
              :loading="loading"
              @click="handleConnect"
            >
              <v-icon start>mdi-wallet</v-icon>
              Connetti Phantom
            </v-btn>

            <!-- Stato: Connessione in corso (pending approval) -->
            <div v-if="connecting && !connected" class="text-center py-4">
              <v-progress-circular
                indeterminate
                color="primary"
                class="mb-3"
              />
              <p class="text-body-2 text-medium-emphasis">
                Approva la connessione su Phantom...
              </p>
              <v-btn
                variant="text"
                size="small"
                class="mt-2"
                @click="cancelConnection"
              >
                Annulla
              </v-btn>
            </div>

            <!-- Stato: Wallet connesso, pronto per firma -->
            <div v-if="connected && !connecting" class="text-center">
              <v-chip color="success" class="mb-4">
                <v-icon start size="small">mdi-check-circle</v-icon>
                {{ walletAddressShort }}
              </v-chip>
              
              <v-btn
                color="primary"
                size="large"
                block
                :loading="loading"
                @click="signAndLogin"
              >
                <v-icon start>mdi-draw</v-icon>
                Firma e Accedi
              </v-btn>
              
              <v-btn
                variant="text"
                class="mt-2"
                @click="handleDisconnect"
              >
                Disconnetti
              </v-btn>
            </div>
          </div>
        </v-card>
        
        <!-- Info box per debug (rimuovi in produzione) -->
        <v-card v-if="showDebug" class="mt-4 pa-3" variant="outlined">
          <p class="text-caption mb-1">🔧 Debug Info:</p>
          <p class="text-caption">Mobile: {{ isMobile }}</p>
          <p class="text-caption">In Phantom: {{ isInPhantomBrowser }}</p>
          <p class="text-caption">Connected: {{ connected }}</p>
          <p class="text-caption">Connecting: {{ connecting }}</p>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useWallet } from 'solana-wallets-vue'
import { useDisplay } from 'vuetify'
import api from '@/lib/axios'

// ============================================
// Props & Config
// ============================================
const showDebug = ref(false) // Metti true per debug

// ============================================
// Vuetify Display (responsive)
// ============================================
const { mobile } = useDisplay()

// ============================================
// Wallet Adapter
// ============================================
const { 
  publicKey,
  connected,
  connecting,
  wallet,
  wallets,
  connect,
  disconnect,
  select,
} = useWallet()

// ============================================
// State locale
// ============================================
const loading = ref(false)
const error = ref(null)
const connectionCancelled = ref(false)

// ============================================
// Mobile Detection
// ============================================
const isMobile = computed(() => {
  // Combina Vuetify display + user agent check
  if (mobile.value) return true
  
  // Fallback per casi edge
  if (typeof navigator !== 'undefined') {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    )
  }
  return false
})

/**
 * Detecta se siamo dentro il browser in-app di Phantom
 * Phantom inietta `window.phantom` anche nel suo browser interno
 */
const isInPhantomBrowser = computed(() => {
  if (typeof window === 'undefined') return false
  
  // Nel browser di Phantom mobile, solana è già disponibile e connesso
  // Inoltre l'user agent contiene "Phantom"
  const isPhantomUA = /Phantom/i.test(navigator.userAgent)
  const hasPhantom = !!window.phantom?.solana
  
  // Se siamo su mobile E Phantom è disponibile, probabilmente siamo nel browser Phantom
  return isMobile.value && hasPhantom || isPhantomUA
})

// ============================================
// Computed
// ============================================
const walletAddressShort = computed(() => {
  if (!publicKey.value) return ''
  const addr = publicKey.value.toBase58()
  return addr.slice(0, 4) + '...' + addr.slice(-4)
})

// ============================================
// Deep Link per Phantom Mobile
// ============================================
const getPhantomDeepLink = () => {
  // URL corrente dell'app (dove Phantom deve tornare dopo la connessione)
  const currentUrl = window.location.href
  
  // Costruisci il deep link per Phantom
  // Formato: https://phantom.app/ul/browse/{URL_ENCODED}
  const encodedUrl = encodeURIComponent(currentUrl)
  
  return `https://phantom.app/ul/browse/${encodedUrl}`
}

const openInPhantomApp = () => {
  const deepLink = getPhantomDeepLink()
  
  // Prova prima con il deep link diretto
  window.location.href = deepLink
}

// ============================================
// Handlers
// ============================================

/**
 * Annulla tentativo di connessione
 */
const cancelConnection = () => {
  connectionCancelled.value = true
  loading.value = false
  error.value = null
}

/**
 * Connetti wallet via wallet adapter
 */
const handleConnect = async () => {
  loading.value = true
  error.value = null
  connectionCancelled.value = false
  
  try {
    // Check se Phantom è installato
    if (!window.phantom?.solana) {
      if (isMobile.value) {
        // Su mobile, suggerisci di usare il browser Phantom
        error.value = 'Apri questa pagina nel browser di Phantom App'
        return
      } else {
        // Su desktop, link per installare estensione
        window.open('https://phantom.app/', '_blank')
        error.value = 'Installa l\'estensione Phantom per continuare'
        return
      }
    }
    
    // Seleziona Phantom
    const phantomWallet = wallets.value.find(w => w.adapter.name === 'Phantom')
    if (phantomWallet) {
      select(phantomWallet.adapter.name)
    }
    
    // Questo trigger la popup di Phantom
    await connect()
    
    // Se arriviamo qui, la connessione è riuscita
    // (il flusso continua con signAndLogin o l'utente clicca manualmente)
    
  } catch (e) {
    console.error('Connect error:', e)
    
    // Non mostrare errore se l'utente ha annullato
    if (connectionCancelled.value) return
    
    // Gestisci errori specifici
    if (e.name === 'WalletNotReadyError') {
      if (isMobile.value) {
        error.value = 'Apri questa pagina nel browser di Phantom'
      } else {
        error.value = 'Phantom non trovato. Installa l\'estensione.'
      }
    } else if (e.name === 'WalletConnectionError') {
      // L'utente ha rifiutato - non è un vero errore
      error.value = 'Connessione annullata'
    } else if (e.name === 'WalletWindowClosedError') {
      // L'utente ha chiuso la popup
      error.value = 'Finestra di Phantom chiusa'
    } else if (e.message?.includes('User rejected')) {
      // Phantom-specific rejection
      error.value = 'Connessione rifiutata'
    } else {
      // Errore generico - sii più specifico
      error.value = 'Errore di connessione. Riprova.'
    }
  } finally {
    loading.value = false
  }
}

/**
 * Disconnetti wallet
 */
const handleDisconnect = async () => {
  try {
    await disconnect()
    error.value = null
  } catch (e) {
    console.error('Disconnect error:', e)
  }
}

/**
 * Firma challenge e login
 */
const signAndLogin = async () => {
  if (!publicKey.value) {
    error.value = 'Wallet non connesso'
    return
  }

  loading.value = true
  error.value = null
  
  try {
    const walletAddress = publicKey.value.toBase58()
    
    // 1. Richiedi challenge al backend
    const { data: challengeData } = await api.post('/auth/challenge', { 
      wallet: walletAddress 
    })
    
    // 2. Firma con wallet adapter
    const message = new TextEncoder().encode(challengeData.nonce)
    const signature = await wallet.value.adapter.signMessage(message)
    
    // 3. Verifica e login
    const { data } = await api.post('/auth/verify', {
      wallet: walletAddress,
      signature: Array.from(signature)
    })
    
    // 4. Redirect con Inertia
    if (data.success) {
      router.visit(data.redirect)
    }
    
  } catch (e) {
    console.error('Sign and login error:', e)
    
    if (e.name === 'WalletSignMessageError' || e.message?.includes('User rejected')) {
      error.value = 'Firma rifiutata'
    } else if (e.response?.status === 401) {
      error.value = 'Firma non valida. Riprova.'
    } else {
      error.value = e.response?.data?.error || 'Errore durante il login'
    }
  } finally {
    loading.value = false
  }
}

// ============================================
// Auto-connect se siamo nel browser Phantom
// ============================================
onMounted(async () => {
  // Se siamo nel browser in-app di Phantom, prova a connetterti automaticamente
  if (isInPhantomBrowser.value && window.phantom?.solana) {
    try {
      // Phantom in-app browser potrebbe già essere connesso
      const resp = await window.phantom.solana.connect({ onlyIfTrusted: true })
      if (resp.publicKey) {
        console.log('Auto-connected in Phantom browser')
        // Il wallet adapter dovrebbe sincronizzarsi automaticamente
      }
    } catch (e) {
      // Non era già connesso, l'utente dovrà cliccare manualmente
      console.log('Not auto-connected, user action required')
    }
  }
})

// ============================================
// Watchers
// ============================================
watch(connected, (isConnected) => {
  console.log('Wallet connected:', isConnected)
  if (isConnected && publicKey.value) {
    console.log('Public key:', publicKey.value.toBase58())
    // Reset error quando ci si connette con successo
    error.value = null
  }
})

// Reset stato quando si disconnette
watch(connecting, (isConnecting) => {
  if (!isConnecting) {
    loading.value = false
  }
})
</script>

<style scoped>
/* Smooth transitions per i cambi di stato */
.v-btn {
  transition: all 0.2s ease;
}

.v-alert {
  transition: all 0.3s ease;
}
</style>