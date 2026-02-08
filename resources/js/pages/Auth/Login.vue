<template>
  <v-container class="login-container fill-height bg-primary-darken-1" fluid>
    <v-row justify="center" align="center" class="login-row">
      <v-col cols="12" sm="8" md="5" lg="4">
        <v-card class="pa-4 pa-sm-6 login-card" elevation="8">
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
          <!-- REGISTER: RECOVERY -->
          <!-- ================================ -->
          <div v-else-if="mode === 'register-recovery'">
            <v-card-subtitle class="text-center mb-4">
              Salva il backup del wallet
            </v-card-subtitle>

            <v-alert type="info" variant="tonal" class="mb-4">
              Per recuperare il tuo wallet in futuro avrai bisogno di <strong>entrambi</strong>:
              il file di backup e il codice di recupero.
            </v-alert>

            <!-- Recovery Code -->
            <v-card variant="outlined" class="pa-3 pa-sm-4 mb-4">
              <div class="d-flex align-center justify-space-between mb-2">
                <span class="text-body-2 font-weight-medium">Codice di Recupero</span>
                <v-btn variant="text" size="small" color="primary" @click="copyRecoveryCode">
                  <v-icon start size="small">mdi-content-copy</v-icon>
                  Copia
                </v-btn>
              </div>
              <div class="recovery-code text-center pa-3 bg-grey-lighten-4 rounded">
                {{ recoveryCode }}
              </div>
              <div class="text-caption text-medium-emphasis mt-2 text-center">
                Annota questo codice in un posto sicuro
              </div>
            </v-card>

            <!-- Download file -->
            <v-card variant="outlined" class="pa-3 pa-sm-4 mb-4">
              <div class="text-body-2 font-weight-medium mb-3">File di Backup</div>
              <v-btn
                color="primary"
                variant="tonal"
                block
                :loading="isDownloading"
                @click="downloadRecoveryFile"
              >
                <v-icon start>mdi-download</v-icon>
                Scarica {{ form.name }}.ecothread
              </v-btn>
              <div class="text-caption text-medium-emphasis mt-2 text-center">
                Conserva questo file al sicuro (cloud, USB, email a te stesso)
              </div>
            </v-card>

            <!-- Wallet address -->
            <v-card variant="flat" class="pa-3 mb-4 bg-grey-lighten-4">
              <div class="text-caption text-medium-emphasis">Indirizzo Wallet</div>
              <code class="text-body-2 wallet-address">{{ publicKey }}</code>
            </v-card>

            <!-- Conferma -->
            <v-checkbox v-model="recoveryConfirmed" color="primary" class="mb-2">
              <template #label>
                <span class="text-body-2">Ho salvato il codice e scaricato il file</span>
              </template>
            </v-checkbox>

            <v-btn
              color="primary"
              size="large"
              block
              :disabled="!recoveryConfirmed || !fileDownloaded"
              @click="mode = 'register-passkey'"
            >
              Continua
            </v-btn>

            <div class="text-center mt-4">
              <v-btn variant="text" @click="mode = 'register-info'">← Indietro</v-btn>
            </div>
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
                <code class="wallet-address">{{ walletAddress }}</code>
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
import { useSnack } from '@/composables/useSnack'
import { downloadBlob } from '@/lib/downloadFile'

const { success: showSuccess, error: showError } = useSnack()

const {
  isPasskeySupported,
  isLoading,
  error,
  walletAddress,
  generateWallet,
  register,
  login,
  checkSession,
  createRecoveryFile,
  getTempRecoveryCode,
  clearTempData,
} = usePasskeyAuth()

type Mode = 'login' | 'register-info' | 'register-recovery' | 'register-passkey' | 'success'

const mode = ref<Mode>('login')
const form = ref({ name: '', email: '' })
const publicKey = ref('')
const recoveryConfirmed = ref(false)
const isDownloading = ref(false)
const fileDownloaded = ref(false)

const recoveryCode = computed(() => getTempRecoveryCode() || '')

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
    fileDownloaded.value = false
    recoveryConfirmed.value = false
    mode.value = 'register-recovery'
  } catch (e: any) {
    error.value = e.message
  }
}

// Copy recovery code
const copyRecoveryCode = async () => {
  await navigator.clipboard.writeText(recoveryCode.value)
  showSuccess('Codice copiato!')
}

// Download recovery file
const downloadRecoveryFile = async () => {
  isDownloading.value = true
  try {
    const blob = await createRecoveryFile(form.value.name)
    const filename = `${form.value.name.replace(/\s+/g, '-').toLowerCase()}.ecothread`
    await downloadBlob(blob, filename)
    fileDownloaded.value = true
    showSuccess('File scaricato!')
  } catch (e: any) {
    showError(e.message)
  } finally {
    isDownloading.value = false
  }
}

// Step 2: Create passkey and register
const handleRegister = async () => {
  try {
    const success = await register(form.value.name, form.value.email || undefined)
    if (success) {
      clearTempData()
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
.login-container {
  min-height: 100vh;
  min-height: 100dvh;
  padding: 16px;
}

.login-row {
  min-height: 100%;
}

.login-card {
  max-height: calc(100vh - 32px);
  max-height: calc(100dvh - 32px);
  overflow-y: auto;
}

.recovery-code {
  font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
  font-size: 1.1rem;
  font-weight: 600;
  letter-spacing: 2px;
  color: #1a1a1a;
  word-break: break-all;
}

.wallet-address {
  word-break: break-all;
}

@media (max-width: 600px) {
  .login-card {
    max-height: calc(100vh - 24px);
    max-height: calc(100dvh - 24px);
  }

  .recovery-code {
    font-size: 0.95rem;
    letter-spacing: 1px;
  }
}
</style>
