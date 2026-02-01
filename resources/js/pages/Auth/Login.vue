<template>
  <v-container class="fill-height d-flex h-screen bg-primary-darken-1" fluid>
    <v-row justify="center" align="center">
      <v-col cols="12" sm="8" md="4">
        <v-card class="pa-6" elevation="8">
          <v-card-title class="text-center text-h4 mb-4">
            🌿 EcoThread
          </v-card-title>
          
          <v-card-subtitle class="text-center mb-6">
            Accedi con il tuo wallet Solana
          </v-card-subtitle>

          <v-alert v-if="error" type="error" class="mb-4" closable @click:close="error = null">
            {{ error }}
          </v-alert>

          <!-- Stato: Wallet non connesso -->
          <v-btn
            v-if="!connected"
            color="primary"
            size="large"
            block
            :loading="loading"
            @click="handleConnect"
          >
            <v-icon left>mdi-wallet</v-icon>
            Connetti Phantom
          </v-btn>

          <!-- Stato: Wallet connesso, pronto per firma -->
          <div v-else class="text-center">
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
              <v-icon left>mdi-draw</v-icon>
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

          <!-- Divider opzionale per wallet-multi-button -->
          <!-- 
          <v-divider class="my-4" />
          <div class="text-center">
            <wallet-multi-button />
          </div>
          -->
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useWallet } from 'solana-wallets-vue'
import api from '@/lib/axios'
import Layout from '../Layout.vue'

// ============================================
// Wallet Adapter
// ============================================
const { 
  publicKey,      // Ref<PublicKey | null>
  connected,      // Ref<boolean>
  connecting,     // Ref<boolean>
  wallet,         // Ref<Wallet | null>
  wallets,        // Ref<Wallet[]>
  connect,        // () => Promise<void>
  disconnect,     // () => Promise<void>
  select,         // (walletName: string) => void
} = useWallet()

// ============================================
// State locale
// ============================================
const loading = ref(false)
const error = ref(null)

// ============================================
// Computed
// ============================================
const walletAddressShort = computed(() => {
  if (!publicKey.value) return ''
  const addr = publicKey.value.toBase58()
  return addr.slice(0, 4) + '...' + addr.slice(-4)
})

// ============================================
// Handlers
// ============================================

/**
 * Connetti wallet via wallet adapter
 */
const handleConnect = async () => {
  loading.value = true
  error.value = null
  
  try {
    // Se Phantom non è installato
    if (!window.phantom?.solana) {
      window.open('https://phantom.app/', '_blank')
      throw new Error('Phantom non installato')
    }
    
    // Seleziona Phantom (primo wallet nella lista)
    const phantomWallet = wallets.value.find(w => w.adapter.name === 'Phantom')
    if (phantomWallet) {
      select(phantomWallet.adapter.name)
    }
    
    await connect()
  } catch (e) {
    console.error('Connect error:', e)
    
    if (e.name === 'WalletNotReadyError') {
      error.value = 'Phantom non trovato. Installa l\'estensione.'
    } else if (e.name === 'WalletConnectionError') {
      error.value = 'Connessione rifiutata'
    } else {
      error.value = 'Connessione al wallet fallita'
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
    
    if (e.name === 'WalletSignMessageError') {
      error.value = 'Firma rifiutata'
    } else {
      error.value = e.response?.data?.error || 'Errore durante il login'
    }
  } finally {
    loading.value = false
  }
}

// ============================================
// Watchers (opzionale - per debug)
// ============================================
watch(connected, (isConnected) => {
  console.log('Wallet connected:', isConnected)
  if (isConnected && publicKey.value) {
    console.log('Public key:', publicKey.value.toBase58())
  }
})

</script>