<template>
  <v-container class="fill-height d-flex h-screen bg-primary-darken-1" fluid>
    <v-row justify="center" align="center">
      <v-col cols="12" sm="8" md="5" lg="4">
        <v-card class="pa-6" elevation="8">
          <!-- Logo -->
          <v-card-title class="text-center mb-4 d-flex justify-center">
            <img src="/logo.png" class="d-none d-md-block" width="300" />
            <img width="100" src="/logo-mobile.png" class="d-block d-md-none" />
          </v-card-title>

          <!-- Error -->
          <v-alert v-if="error" type="error" class="mb-4" closable @click:close="error = null">
            {{ error }}
          </v-alert>

          <!-- Passkey not supported -->
          <v-alert v-if="!isPasskeySupported" type="warning" class="mb-4">
            Il tuo browser non supporta le passkey. Usa Chrome, Safari o Edge.
          </v-alert>

          <!-- ================================ -->
          <!-- LOGIN -->
          <!-- ================================ -->
          <div v-if="mode === 'login' && isPasskeySupported">
            <v-card-subtitle class="text-center mb-6">
              Accedi con la tua passkey
            </v-card-subtitle>

            <v-btn color="primary" size="large" block :loading="isLoading" @click="handleLogin">
              <v-icon start>mdi-fingerprint</v-icon>
              Accedi con Passkey
            </v-btn>

            <div class="text-center mt-6">
              <span class="text-medium-emphasis">Non hai un account?</span>
              <v-btn variant="text" color="primary" @click="mode = 'register-info'">
                Registrati
              </v-btn>
            </div>
          </div>

          <!-- ================================ -->
          <!-- REGISTER: INFO -->
          <!-- ================================ -->
          <div v-else-if="mode === 'register-info'">
            <v-card-subtitle class="text-center mb-6">
              Crea il tuo account
            </v-card-subtitle>

            <v-form @submit.prevent="handleGenerateWallet">
              <v-text-field
                v-model="form.name"
                label="Nome Azienda"
                variant="outlined"
                class="mb-3"
                :rules="[v => !!v || 'Obbligatorio']"
              />

              <v-text-field
                v-model="form.email"
                label="Email (opzionale)"
                type="email"
                variant="outlined"
                class="mb-4"
              />

              <v-btn color="primary" size="large" block type="submit" :loading="isLoading">
                Continua
              </v-btn>
            </v-form>

            <div class="text-center mt-4">
              <v-btn variant="text" @click="mode = 'login'">← Torna al login</v-btn>
            </div>
          </div>

          <!-- ================================ -->
          <!-- REGISTER: RECOVERY PHRASE -->
          <!-- ================================ -->
          <div v-else-if="mode === 'register-recovery'">
            <v-card-subtitle class="text-center mb-4">
              Salva la Recovery Phrase
            </v-card-subtitle>

            <v-alert type="warning" variant="tonal" class="mb-4">
              <strong>IMPORTANTE:</strong> Queste 24 parole sono l'unico modo per recuperare il wallet.
              Scrivile su carta!
            </v-alert>

            <v-card variant="outlined" class="pa-4 mb-4 bg-grey-lighten-4">
              <div class="recovery-grid">
                <div v-for="(word, i) in mnemonicWords" :key="i" class="recovery-word">
                  <span class="word-num">{{ i + 1 }}.</span>
                  <span>{{ word }}</span>
                </div>
              </div>
            </v-card>

            <v-card variant="outlined" class="pa-3 mb-4">
              <div class="text-caption text-medium-emphasis">Wallet Address</div>
              <code>{{ publicKey }}</code>
            </v-card>

            <v-checkbox v-model="recoveryConfirmed" color="primary" class="mb-2">
              <template #label>
                <span class="text-body-2">Ho salvato la recovery phrase</span>
              </template>
            </v-checkbox>

            <v-btn
              color="primary"
              size="large"
              block
              :disabled="!recoveryConfirmed"
              @click="mode = 'register-passkey'"
            >
              Continua
            </v-btn>
          </div>

          <!-- ================================ -->
          <!-- REGISTER: CREATE PASSKEY -->
          <!-- ================================ -->
          <div v-else-if="mode === 'register-passkey'">
            <v-card-subtitle class="text-center mb-4">
              Proteggi il tuo account
            </v-card-subtitle>

            <div class="text-center mb-6">
              <v-icon size="64" color="primary" class="mb-4">mdi-fingerprint</v-icon>
              <p class="text-body-2 text-medium-emphasis">
                Usa Face ID, Touch ID o PIN per proteggere il tuo account e wallet.
              </p>
            </div>

            <v-btn color="primary" size="large" block :loading="isLoading" @click="handleRegister">
              <v-icon start>mdi-shield-check</v-icon>
              Crea Passkey
            </v-btn>

            <div class="text-center mt-4">
              <v-btn variant="text" @click="mode = 'register-recovery'">← Indietro</v-btn>
            </div>
          </div>

          <!-- ================================ -->
          <!-- SUCCESS -->
          <!-- ================================ -->
          <div v-else-if="mode === 'success'">
            <div class="text-center">
              <v-icon size="64" color="success" class="mb-4">mdi-check-circle</v-icon>
              <h3 class="text-h6 mb-2">Account Creato!</h3>
              <p class="text-medium-emphasis mb-4">
                Il tuo wallet è pronto per registrare eventi sulla blockchain.
              </p>

              <v-card variant="outlined" class="pa-3 mb-6 text-left">
                <div class="text-caption text-medium-emphasis">Wallet</div>
                <code>{{ walletAddress }}</code>
              </v-card>

              <v-btn color="primary" size="large" block @click="goToDashboard">
                Vai alla Dashboard
              </v-btn>
            </div>
          </div>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { usePasskeyAuth } from '@/composables/usePasskeyAuth'
import { route } from 'ziggy-js'

const {
  isPasskeySupported,
  isLoading,
  error,
  walletAddress,
  generateWallet,
  register,
  login,
  checkSession,
  getTempMnemonic,
  clearTempMnemonic,
} = usePasskeyAuth()

type Mode = 'login' | 'register-info' | 'register-recovery' | 'register-passkey' | 'success'

const mode = ref<Mode>('login')
const form = ref({ name: '', email: '' })
const publicKey = ref('')
const recoveryConfirmed = ref(false)

const mnemonicWords = computed(() => getTempMnemonic()?.split(' ') || [])

// Login
const handleLogin = async () => {
  try {
    const success = await login()
    if (success) router.visit(route('admin.dashboard'))
  } catch {
    // Error handled in composable
  }
}

// Step 1: Generate wallet
const handleGenerateWallet = async () => {
  if (!form.value.name) return
  
  try {
    const result = await generateWallet()
    publicKey.value = result.publicKey
    mode.value = 'register-recovery'
  } catch (e: any) {
    error.value = e.message
  }
}

// Step 2: Create passkey and register
const handleRegister = async () => {
  try {
    const success = await register(form.value.name, form.value.email || undefined)
    if (success) {
      clearTempMnemonic()
      mode.value = 'success'
    }
  } catch {
    // Error handled in composable
  }
}

const goToDashboard = () => router.visit(route('admin.dashboard'))

onMounted(async () => {
  const hasSession = await checkSession()
  if (hasSession) router.visit(route('admin.dashboard'))
})
</script>

<style scoped>
.recovery-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}

@media (max-width: 600px) {
  .recovery-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.recovery-word {
  display: flex;
  gap: 4px;
  padding: 8px;
  background: white;
  border-radius: 4px;
  font-family: monospace;
  font-size: 0.875rem;
}

.word-num {
  color: #666;
  min-width: 24px;
}
</style>