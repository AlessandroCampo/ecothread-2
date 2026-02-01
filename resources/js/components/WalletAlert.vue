<template>
 <!-- Alert: Wallet non connesso - CON BOTTONE CONNECT -->
<v-alert 
  v-if="!isWalletConnected" 
  type="warning" 
  variant="tonal"
  class="mb-4"
>
  <div class="d-flex align-center justify-space-between">
    <div>
      <v-alert-title>Wallet non connesso</v-alert-title>
      Per scrivere dati on-chain devi connettere Phantom.
    </div>
    <v-btn
      color="warning"
      variant="flat"
      size="small"
      :loading="connecting"
      @click="connectWallet"
    >
      <v-icon start>mdi-wallet</v-icon>
      Connetti
    </v-btn>
  </div>
</v-alert>
  
</template>

<script setup>

import { useSolana } from '@/composables/useSolana'

const { isWalletConnected, connectPhantom, connecting } = useSolana()

async function connectWallet() {
  try {
    await connectPhantom()
  } catch (e) {
    console.error('Connection failed:', e)
  }
}

</script>