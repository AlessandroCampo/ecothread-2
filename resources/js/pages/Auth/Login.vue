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

          <!-- FLUSSO 1: Phantom disponibile (desktop extension o Phantom in-app browser) -->
          <div v-if="hasPhantomExtension">
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

            <div v-if="connecting && !connected" class="text-center py-4">
              <v-progress-circular indeterminate color="primary" class="mb-3" />
              <p class="text-body-2 text-medium-emphasis">
                Approva la connessione su Phantom...
              </p>
              <v-btn variant="text" size="small" class="mt-2" @click="cancelConnection">
                Annulla
              </v-btn>
            </div>

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

              <v-btn variant="text" class="mt-2" @click="handleDisconnect">
                Disconnetti
              </v-btn>
            </div>
          </div>

          <!-- FLUSSO 2: Mobile senza estensione Phantom → deep link protocol -->
          <div v-else-if="isMobileDevice">
            <div v-if="!mobileConnected">
              <v-btn
                color="primary"
                size="large"
                block
                :loading="loading"
                @click="mobileConnect"
              >
                <v-icon start>mdi-wallet</v-icon>
                Connetti Phantom
              </v-btn>

              <p class="text-caption text-center mt-4 text-medium-emphasis">
                Si aprirà Phantom per approvare la connessione
              </p>
            </div>

            <div v-else class="text-center">
              <v-chip color="success" class="mb-4">
                <v-icon start size="small">mdi-check-circle</v-icon>
                {{ mobileWalletShort }}
              </v-chip>

              <v-btn
                color="primary"
                size="large"
                block
                :loading="loading"
                @click="mobileSignAndLogin"
              >
                <v-icon start>mdi-draw</v-icon>
                Firma e Accedi
              </v-btn>

              <v-btn variant="text" class="mt-2" @click="mobileDisconnect">
                Disconnetti
              </v-btn>
            </div>
          </div>

          <!-- FLUSSO 3: Desktop senza Phantom → installa -->
          <div v-else>
            <v-btn
              color="primary"
              size="large"
              block
              href="https://phantom.app/"
              target="_blank"
            >
              <v-icon start>mdi-download</v-icon>
              Installa Phantom
            </v-btn>

            <p class="text-caption text-center mt-4 text-medium-emphasis">
              Installa l'estensione Phantom per accedere
            </p>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useWallet } from 'solana-wallets-vue'
import { usePhantomMobile } from '@/composables/usePhantomMobile'
import api from '@/lib/axios'

// ============================================
// Phantom Mobile (deep links)
// ============================================
const phantom = usePhantomMobile()
const mobileConnected = ref(false)
const mobileWalletAddress = ref<string | null>(null)

const mobileWalletShort = computed(() => {
  if (!mobileWalletAddress.value) return ''
  const addr = mobileWalletAddress.value
  return addr.slice(0, 4) + '...' + addr.slice(-4)
})

// ============================================
// Wallet Adapter (desktop/Phantom browser)
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
// State
// ============================================
const loading = ref(false)
const error = ref<string | null>(null)
const connectionCancelled = ref(false)

// ============================================
// Detection: semplificata
// ============================================

// Phantom extension/in-app browser disponibile?
const hasPhantomExtension = ref(false)

// Siamo su mobile?
const isMobileDevice = computed(() => {
  if (typeof navigator === 'undefined') return false
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
    navigator.userAgent
  )
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
// MOBILE: Connect via deep link
// ============================================
const mobileConnect = () => {
  loading.value = true
  error.value = null
  sessionStorage.setItem('phantom_pending_action', 'connect')
  phantom.connect()
}

// ============================================
// MOBILE: Sign e Login via deep link
// ============================================
const mobileSignAndLogin = async () => {
  const walletAddress = mobileWalletAddress.value
  if (!walletAddress) {
    error.value = 'Wallet non connesso'
    return
  }

  loading.value = true
  error.value = null

  try {
    const { data: challengeData } = await api.post('/auth/challenge', {
      wallet: walletAddress,
    })

    sessionStorage.setItem('phantom_pending_action', 'sign')
    sessionStorage.setItem('phantom_challenge_nonce', challengeData.nonce)

    phantom.signMessage(challengeData.nonce)
  } catch (e) {
    console.error('Challenge error:', e)
    error.value = 'Errore durante la richiesta di challenge'
    loading.value = false
  }
}

// ============================================
// MOBILE: Gestisci risposta deep link
// ============================================
const handleDeepLink = async (url: string) => {
  const pendingAction = sessionStorage.getItem('phantom_pending_action')
  sessionStorage.removeItem('phantom_pending_action')

  if (pendingAction === 'connect') {
    const result = phantom.handleConnectResponse(url)
    if (result) {
      mobileConnected.value = true
      mobileWalletAddress.value = result.publicKey
      error.value = null
    } else {
      error.value = 'Connessione a Phantom fallita'
    }
    loading.value = false
  } else if (pendingAction === 'sign') {
    const result = phantom.handleSignResponse(url)
    if (result) {
      const walletAddress = sessionStorage.getItem('phantom_public_key')

      if (!walletAddress) {
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
      } catch (e: any) {
        console.error('Verify error:', e)
        error.value = e.response?.data?.error || 'Errore durante il login'
      }
    } else {
      error.value = 'Firma rifiutata o fallita'
    }
    loading.value = false
  }
}

// ============================================
// MOBILE: Disconnetti
// ============================================
const mobileDisconnect = () => {
  phantom.disconnect()
  mobileConnected.value = false
  mobileWalletAddress.value = null
  error.value = null
}

// ============================================
// DESKTOP: Handlers wallet adapter
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
    const phantomWallet = wallets.value.find((w: any) => w.adapter.name === 'Phantom')
    if (phantomWallet) {
      select(phantomWallet.adapter.name)
    }
    await connect()
  } catch (e: any) {
    console.error('Connect error:', e)
    if (connectionCancelled.value) return

    if (e.name === 'WalletConnectionError' || e.message?.includes('User rejected')) {
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
      wallet: walletAddress,
    })

    const message = new TextEncoder().encode(challengeData.nonce)
    const signature = await wallet.value!.adapter.signMessage!(message)

    const { data } = await api.post('/auth/verify', {
      wallet: walletAddress,
      signature: Array.from(signature),
    })

    if (data.success) {
      router.visit(data.redirect)
    }
  } catch (e: any) {
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
  // Controlla se window.phantom.solana esiste (estensione desktop o Phantom browser)
  // Aspetta un attimo perché potrebbe essere iniettato dopo il DOM ready
  await new Promise((r) => setTimeout(r, 300))
  hasPhantomExtension.value = !!window.phantom?.solana

  if (hasPhantomExtension.value) {
    // Auto-connect se siamo nel browser Phantom
    try {
      const resp = await window.phantom.solana.connect({ onlyIfTrusted: true })
      if (resp.publicKey) {
        console.log('Auto-connected in Phantom browser')
      }
    } catch (e) {
      // Non era già connesso
    }
    return
  }

  // Mobile: controlla se c'è una connessione salvata
  if (isMobileDevice.value) {
    const storedPk = phantom.getStoredPublicKey()
    if (storedPk) {
      mobileConnected.value = true
      mobileWalletAddress.value = storedPk
    }

    // Ascolta deep link di ritorno (se Capacitor bridge è disponibile)
    const Capacitor = (window as any).Capacitor
    if (Capacitor?.Plugins?.App) {
      Capacitor.Plugins.App.addListener('appUrlOpen', (event: any) => {
        if (event.url && event.url.startsWith('ecothread://phantom')) {
          handleDeepLink(event.url)
        }
      })
    }

    // Fallback: controlla URL params per risposta Phantom (browser redirect)
    const currentUrl = window.location.href
    if (currentUrl.includes('phantom_encryption_public_key') || currentUrl.includes('errorCode')) {
      const pendingAction = sessionStorage.getItem('phantom_pending_action')
      if (pendingAction) {
        handleDeepLink(currentUrl)
      }
    }
  }
})

// ============================================
// Watchers
// ============================================
watch(connected, (isConnected) => {
  if (isConnected && publicKey.value) {
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
