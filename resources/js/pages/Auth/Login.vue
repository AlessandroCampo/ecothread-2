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

          <!-- CAPACITOR APP: Deep link protocol Phantom -->
          <div v-if="isInCapacitor">
            <!-- Non connesso a Phantom -->
            <div v-if="!capacitorConnected">
              <v-btn
                color="primary"
                size="large"
                block
                :loading="loading"
                @click="capacitorConnect"
              >
                <v-icon start>mdi-wallet</v-icon>
                Connetti Phantom
              </v-btn>

              <p class="text-caption text-center mt-4 text-medium-emphasis">
                Verrai reindirizzato a Phantom per approvare la connessione
              </p>
            </div>

            <!-- Connesso, pronto per firma -->
            <div v-else class="text-center">
              <v-chip color="success" class="mb-4">
                <v-icon start size="small">mdi-check-circle</v-icon>
                {{ capacitorWalletShort }}
              </v-chip>

              <v-btn
                color="primary"
                size="large"
                block
                :loading="loading"
                @click="capacitorSignAndLogin"
              >
                <v-icon start>mdi-draw</v-icon>
                Firma e Accedi
              </v-btn>

              <v-btn
                variant="text"
                class="mt-2"
                @click="capacitorDisconnect"
              >
                Disconnetti
              </v-btn>
            </div>
          </div>

          <!-- MOBILE BROWSER (non Capacitor): Deep link a Phantom App -->
          <div v-else-if="isMobile && !isInPhantomBrowser">
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
          <p class="text-caption mb-1">Debug Info:</p>
          <p class="text-caption">Capacitor: {{ isInCapacitor }}</p>
          <p class="text-caption">Mobile: {{ isMobile }}</p>
          <p class="text-caption">In Phantom: {{ isInPhantomBrowser }}</p>
          <p class="text-caption">Connected: {{ connected }}</p>
          <p class="text-caption">Cap Connected: {{ capacitorConnected }}</p>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useWallet } from 'solana-wallets-vue'
import { useDisplay } from 'vuetify'
import { usePhantomMobile } from '@/composables/usePhantomMobile'
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
// Phantom Mobile (Capacitor deep links)
// ============================================
const phantom = usePhantomMobile()
const isInCapacitor = ref(phantom.isCapacitor())
const capacitorConnected = ref(false)
const capacitorWalletAddress = ref(null)

const capacitorWalletShort = computed(() => {
  if (!capacitorWalletAddress.value) return ''
  const addr = capacitorWalletAddress.value
  return addr.slice(0, 4) + '...' + addr.slice(-4)
})

// ============================================
// Wallet Adapter (per desktop/browser)
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
  if (mobile.value) return true
  if (typeof navigator !== 'undefined') {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    )
  }
  return false
})

const isInPhantomBrowser = computed(() => {
  if (typeof window === 'undefined') return false
  const isPhantomUA = /Phantom/i.test(navigator.userAgent)
  const hasPhantom = !!window.phantom?.solana
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
// Deep Link per Phantom Mobile (browser, non Capacitor)
// ============================================
const getPhantomDeepLink = () => {
  const currentUrl = window.location.href
  const encodedUrl = encodeURIComponent(currentUrl)
  return `https://phantom.app/ul/browse/${encodedUrl}`
}

const openInPhantomApp = () => {
  const deepLink = getPhantomDeepLink()
  window.location.href = deepLink
}

// ============================================
// CAPACITOR: Connect via deep link
// ============================================
const capacitorConnect = () => {
  loading.value = true
  error.value = null
  // Salva stato per sapere che stavamo connettendo
  sessionStorage.setItem('phantom_pending_action', 'connect')
  phantom.connect()
}

// ============================================
// CAPACITOR: Sign e Login via deep link
// ============================================
const capacitorSignAndLogin = async () => {
  const walletAddress = capacitorWalletAddress.value
  if (!walletAddress) {
    error.value = 'Wallet non connesso'
    return
  }

  loading.value = true
  error.value = null

  try {
    // 1. Richiedi challenge dal backend
    const { data: challengeData } = await api.post('/auth/challenge', {
      wallet: walletAddress,
    })

    // 2. Salva il nonce per dopo (quando torniamo dal deep link)
    sessionStorage.setItem('phantom_pending_action', 'sign')
    sessionStorage.setItem('phantom_challenge_nonce', challengeData.nonce)

    // 3. Invia a Phantom per la firma
    phantom.signMessage(challengeData.nonce)
  } catch (e) {
    console.error('Challenge error:', e)
    error.value = 'Errore durante la richiesta di challenge'
    loading.value = false
  }
}

// ============================================
// CAPACITOR: Gestisci risposta deep link
// ============================================
const handleDeepLink = async (url) => {
  const pendingAction = sessionStorage.getItem('phantom_pending_action')
  sessionStorage.removeItem('phantom_pending_action')

  if (pendingAction === 'connect') {
    const result = phantom.handleConnectResponse(url)
    if (result) {
      capacitorConnected.value = true
      capacitorWalletAddress.value = result.publicKey
      error.value = null
    } else {
      error.value = 'Connessione a Phantom fallita'
    }
    loading.value = false
  } else if (pendingAction === 'sign') {
    const result = phantom.handleSignResponse(url)
    if (result) {
      // Completa il login con il backend
      const walletAddress = sessionStorage.getItem('phantom_public_key')
      const nonce = sessionStorage.getItem('phantom_challenge_nonce')

      if (!walletAddress || !nonce) {
        error.value = 'Sessione scaduta. Riprova.'
        loading.value = false
        return
      }

      try {
        const { data } = await api.post('/auth/verify', {
          wallet: walletAddress,
          signature: Array.from(result.signature),
        })

        if (data.success) {
          sessionStorage.removeItem('phantom_challenge_nonce')
          router.visit(data.redirect)
        }
      } catch (e) {
        console.error('Verify error:', e)
        if (e.response?.status === 401) {
          error.value = 'Firma non valida. Riprova.'
        } else {
          error.value = e.response?.data?.error || 'Errore durante il login'
        }
      }
    } else {
      error.value = 'Firma rifiutata o fallita'
    }
    loading.value = false
  }
}

// ============================================
// CAPACITOR: Disconnetti
// ============================================
const capacitorDisconnect = () => {
  phantom.disconnect()
  capacitorConnected.value = false
  capacitorWalletAddress.value = null
  error.value = null
}

// ============================================
// Handlers Desktop/Browser
// ============================================
const cancelConnection = () => {
  connectionCancelled.value = true
  loading.value = false
  error.value = null
}

const handleConnect = async () => {
  loading.value = true
  error.value = null
  connectionCancelled.value = false

  try {
    if (!window.phantom?.solana) {
      if (isMobile.value) {
        error.value = 'Apri questa pagina nel browser di Phantom App'
        return
      } else {
        window.open('https://phantom.app/', '_blank')
        error.value = 'Installa l\'estensione Phantom per continuare'
        return
      }
    }

    const phantomWallet = wallets.value.find(w => w.adapter.name === 'Phantom')
    if (phantomWallet) {
      select(phantomWallet.adapter.name)
    }

    await connect()
  } catch (e) {
    console.error('Connect error:', e)
    if (connectionCancelled.value) return

    if (e.name === 'WalletNotReadyError') {
      if (isMobile.value) {
        error.value = 'Apri questa pagina nel browser di Phantom'
      } else {
        error.value = 'Phantom non trovato. Installa l\'estensione.'
      }
    } else if (e.name === 'WalletConnectionError') {
      error.value = 'Connessione annullata'
    } else if (e.name === 'WalletWindowClosedError') {
      error.value = 'Finestra di Phantom chiusa'
    } else if (e.message?.includes('User rejected')) {
      error.value = 'Connessione rifiutata'
    } else {
      error.value = 'Errore di connessione. Riprova.'
    }
  } finally {
    loading.value = false
  }
}

const handleDisconnect = async () => {
  try {
    await disconnect()
    error.value = null
  } catch (e) {
    console.error('Disconnect error:', e)
  }
}

const signAndLogin = async () => {
  if (!publicKey.value) {
    error.value = 'Wallet non connesso'
    return
  }

  loading.value = true
  error.value = null

  try {
    const walletAddress = publicKey.value.toBase58()

    const { data: challengeData } = await api.post('/auth/challenge', {
      wallet: walletAddress
    })

    const message = new TextEncoder().encode(challengeData.nonce)
    const signature = await wallet.value.adapter.signMessage(message)

    const { data } = await api.post('/auth/verify', {
      wallet: walletAddress,
      signature: Array.from(signature)
    })

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
// Lifecycle
// ============================================
onMounted(async () => {
  // CAPACITOR: Controlla se stiamo tornando da un deep link Phantom
  if (isInCapacitor.value) {
    // Controlla se c'è già una connessione salvata
    const storedPk = phantom.getStoredPublicKey()
    if (storedPk) {
      capacitorConnected.value = true
      capacitorWalletAddress.value = storedPk
    }

    // Ascolta deep link di ritorno da Phantom
    // Capacitor inietta il bridge anche con server.url remoto
    const Capacitor = (window as any).Capacitor
    if (Capacitor?.Plugins?.App) {
      Capacitor.Plugins.App.addListener('appUrlOpen', (event) => {
        if (event.url && event.url.startsWith('ecothread://phantom')) {
          handleDeepLink(event.url)
        }
      })
    }

    // Controlla anche se siamo stati aperti con un deep link (cold start)
    if (window.location.href.includes('ecothread://phantom')) {
      handleDeepLink(window.location.href)
    }

    return
  }

  // Browser Phantom: auto-connect
  if (isInPhantomBrowser.value && window.phantom?.solana) {
    try {
      const resp = await window.phantom.solana.connect({ onlyIfTrusted: true })
      if (resp.publicKey) {
        console.log('Auto-connected in Phantom browser')
      }
    } catch (e) {
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
    error.value = null
  }
})

watch(connecting, (isConnecting) => {
  if (!isConnecting) {
    loading.value = false
  }
})
</script>

<style scoped>
.v-btn {
  transition: all 0.2s ease;
}

.v-alert {
  transition: all 0.3s ease;
}
</style>
