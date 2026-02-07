<template>
  <v-alert 
    v-if="!isWalletConnected" 
    type="warning" 
    variant="tonal"
    class="mb-4"
  >
    <div class="d-flex align-center justify-space-between">
      <div>
        <v-alert-title>Accesso richiesto</v-alert-title>
        Per scrivere dati on-chain devi effettuare il login.
      </div>
      <v-btn
        color="warning"
        variant="flat"
        size="small"
        :loading="isLoading"
        @click="handleLogin"
      >
        <v-icon start>mdi-fingerprint</v-icon>
        Accedi con Passkey
      </v-btn>
    </div>
  </v-alert>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { usePasskeyAuth } from '@/composables/usePasskeyAuth'

const page = usePage()
const { login, isLoading } = usePasskeyAuth()

// Usa i props Inertia come source of truth
const isWalletConnected = computed(() => !!page.props.user?.wallet_address)

async function handleLogin() {
  try {
    await login()
  } catch (e) {
    console.error('Login failed:', e)
  }
}
</script>