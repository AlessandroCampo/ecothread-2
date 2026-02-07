/**
 * usePasskeyAuth - Autenticazione passkey + wallet Solana
 * 
 * Librerie:
 * - @simplewebauthn/browser per WebAuthn
 * - @scure/bip39 per mnemonic (browser-native)
 * - @solana/web3.js per keypair
 */

import { ref, computed, readonly } from 'vue'
import { startRegistration, startAuthentication } from '@simplewebauthn/browser'
import { generateMnemonic, mnemonicToSeedSync, validateMnemonic } from '@scure/bip39'
import { wordlist } from '@scure/bip39/wordlists/english.js'
import { Keypair, PublicKey } from '@solana/web3.js'
import nacl from 'tweetnacl'
import api from '@/lib/axios'

// ============================================
// TYPES
// ============================================

interface User {
  id: number
  name: string
  wallet_address: string
  encrypted_private_key?: string
  encryption_salt?: string
}

// ============================================
// STATE
// ============================================

const user = ref<User | null>(null)
const isAuthenticated = ref(false)
const isWalletUnlocked = ref(false)
const isLoading = ref(false)
const error = ref<string | null>(null)

// In-memory only
let decryptedKeypair: Keypair | null = null
let tempMnemonic: string | null = null
let tempKeypair: Keypair | null = null

// ============================================
// COMPOSABLE
// ============================================

export function usePasskeyAuth() {

  const isPasskeySupported = computed(() => {
    return !!window.PublicKeyCredential
  })

  // ==========================================
  // GENERATE WALLET
  // ==========================================

  const generateWallet = async (): Promise<{ mnemonic: string; publicKey: string }> => {
    // Genera mnemonic con @scure/bip39 (browser-native!)
    const mnemonic = generateMnemonic(wordlist, 256) // 24 parole
    
    // Deriva seed
    const seed = mnemonicToSeedSync(mnemonic)
    
    // Usa i primi 32 byte per Solana keypair
    const keypair = Keypair.fromSeed(seed.slice(0, 32))
    
    tempMnemonic = mnemonic
    tempKeypair = keypair
    
    return {
      mnemonic,
      publicKey: keypair.publicKey.toBase58()
    }
  }

  // ==========================================
  // REGISTER
  // ==========================================

  const register = async (name: string): Promise<boolean> => {
    if (!tempKeypair) {
      throw new Error('Genera prima il wallet')
    }

    isLoading.value = true
    error.value = null

    try {
      // 1. Ottieni opzioni dal server
      const { data: options } = await api.post('/auth/register/options', {
        name,
        wallet_address: tempKeypair.publicKey.toBase58(),
      })

      // 2. Crea passkey con simplewebauthn (gestisce tutto!)
      const credential = await startRegistration({ optionsJSON: options })

      // 3. Cripta private key
      const salt = crypto.getRandomValues(new Uint8Array(32))
      const encryptedPrivateKey = await encryptPrivateKey(tempKeypair.secretKey, salt)

      // 4. Invia al server
      const { data: result } = await api.post('/auth/register/verify', {
        ...credential,
        encrypted_private_key: encryptedPrivateKey,
        encryption_salt: bufferToBase64(salt),
      })

      if (result.success) {
        user.value = result.user
        isAuthenticated.value = true
        isWalletUnlocked.value = true
        decryptedKeypair = tempKeypair
        tempMnemonic = null
        tempKeypair = null
        return true
      }

      return false
    } catch (e: any) {
      error.value = e.response?.data?.error || e.message || 'Errore registrazione'
      throw e
    } finally {
      isLoading.value = false
    }
  }

  // ==========================================
  // LOGIN
  // ==========================================

  const login = async (): Promise<boolean> => {
    isLoading.value = true
    error.value = null

    try {
      // 1. Ottieni opzioni
      const { data: options } = await api.post('/auth/login/options')

      // 2. Autentica con simplewebauthn
      const credential = await startAuthentication({ optionsJSON: options })

      // 3. Verifica sul server
      const { data: result } = await api.post('/auth/login/verify', credential)

      if (result.success) {
        user.value = result.user
        isAuthenticated.value = true
        isWalletUnlocked.value = false
        return true
      }

      return false
    } catch (e: any) {
      if (e.name === 'NotAllowedError') {
        error.value = 'Login annullato'
      } else {
        error.value = e.response?.data?.error || e.message || 'Errore login'
      }
      throw e
    } finally {
      isLoading.value = false
    }
  }

  // ==========================================
  // UNLOCK WALLET
  // ==========================================

  const unlockWallet = async (): Promise<boolean> => {
    if (decryptedKeypair) {
      isWalletUnlocked.value = true
      return true
    }

    if (!user.value?.encrypted_private_key) {
      throw new Error('Nessun wallet da sbloccare')
    }

    isLoading.value = true

    try {
      // Richiede autenticazione passkey per sbloccare
      const { data: options } = await api.post('/auth/login/options')
      await startAuthentication({ optionsJSON: options })

      // Decripta
      const salt = base64ToBuffer(user.value.encryption_salt!)
      const privateKey = await decryptPrivateKey(user.value.encrypted_private_key!, salt)
      
      decryptedKeypair = Keypair.fromSecretKey(privateKey)
      isWalletUnlocked.value = true
      return true
    } catch (e: any) {
      error.value = e.message || 'Errore sblocco'
      return false
    } finally {
      isLoading.value = false
    }
  }

  // ==========================================
  // SIGN EVENT
  // ==========================================

  const signEvent = async (eventData: {
    product_id: string
    event_type: string
    data_hash: string
    timestamp?: number
  }): Promise<{ message: string; signature: string; publicKey: string }> => {
    if (!decryptedKeypair) {
      const unlocked = await unlockWallet()
      if (!unlocked) throw new Error('Wallet non sbloccato')
    }

    const payload = { ...eventData, timestamp: eventData.timestamp || Date.now() }
    const message = JSON.stringify(payload)
    const messageBytes = new TextEncoder().encode(message)
    
    // Firma con @noble/ed25519
    const signature = nacl.sign.detached(messageBytes, decryptedKeypair!.secretKey)


    return {
      message,
      signature: bufferToBase64(signature),
      publicKey: decryptedKeypair!.publicKey.toBase58(),
    }
  }

  // ==========================================
  // SESSION
  // ==========================================

  const checkSession = async (): Promise<boolean> => {
    try {
      const { data } = await api.get('/auth/session')
      if (data.authenticated) {
        user.value = data.user
        isAuthenticated.value = true
        return true
      }
    } catch {
      // Non autenticato
    }
    return false
  }

  const logout = async (): Promise<void> => {
    try {
      await api.post('/auth/logout')
    } catch {}
    
    user.value = null
    isAuthenticated.value = false
    isWalletUnlocked.value = false
    decryptedKeypair = null
  }

  const lockWallet = (): void => {
    decryptedKeypair = null
    isWalletUnlocked.value = false
  }

  // ==========================================
  // RECOVERY
  // ==========================================

  const recoverFromMnemonic = async (mnemonic: string): Promise<string> => {
    if (!validateMnemonic(mnemonic, wordlist)) {
      throw new Error('Recovery phrase non valida')
    }

    const seed = mnemonicToSeedSync(mnemonic)
    const keypair = Keypair.fromSeed(seed.slice(0, 32))

    tempKeypair = keypair
    return keypair.publicKey.toBase58()
  }

  const signTransaction = async (messageBytes: Uint8Array): Promise<{
  signature: string
  publicKey: string
}> => {
  if (!decryptedKeypair) {
    const unlocked = await unlockWallet()
    if (!unlocked) throw new Error('Wallet non sbloccato')
  }

  // Con tweetnacl (già installato)
  const signature = nacl.sign.detached(messageBytes, decryptedKeypair!.secretKey)

  return {
    signature: bufferToBase64(Buffer.from(signature)),
    publicKey: decryptedKeypair!.publicKey.toBase58(),
  }
}


const getPublicKey = (): PublicKey | null => {
  if (decryptedKeypair) {
    return decryptedKeypair.publicKey
  }
  if (user.value?.wallet_address) {
    return new PublicKey(user.value.wallet_address)
  }
  return null
}

const initFromUser = (inertiaUser: User | null) => {
  if (inertiaUser) {
    user.value = inertiaUser
    isAuthenticated.value = true
    isWalletUnlocked.value = false // Wallet sempre bloccato al refresh
  }
}

  // ==========================================
  // RETURN
  // ==========================================

  return {
    // State
    user: computed(() => user.value),
    isAuthenticated: computed(() => isAuthenticated.value),
    walletAddress: computed(() => user.value?.wallet_address || null),
    isWalletUnlocked: computed(() => isWalletUnlocked.value),
    isLoading: readonly(isLoading),
    error: readonly(error),
    isPasskeySupported,
    initFromUser,
    
    // Auth
    generateWallet,
    register,
    login,
    checkSession,
    logout,
    
    // Wallet
    unlockWallet,
    lockWallet,
    signEvent,
     signTransaction,
  getPublicKey,
    // Recovery
    recoverFromMnemonic,
    getTempMnemonic: () => tempMnemonic,
    clearTempMnemonic: () => { tempMnemonic = null },
  }
}

// ============================================
// HELPERS (encryption)
// ============================================

async function encryptPrivateKey(privateKey: Uint8Array, salt: Uint8Array): Promise<string> {
  const key = await deriveKey(salt)
  const iv = crypto.getRandomValues(new Uint8Array(12))
  
  const encrypted = await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv },
    key,
    privateKey
  )

  // IV + ciphertext
  const combined = new Uint8Array(iv.length + encrypted.byteLength)
  combined.set(iv)
  combined.set(new Uint8Array(encrypted), iv.length)
  
  return bufferToBase64(combined)
}

async function decryptPrivateKey(encryptedBase64: string, salt: Uint8Array): Promise<Uint8Array> {
  const key = await deriveKey(salt)
  const combined = base64ToBuffer(encryptedBase64)
  
  const iv = combined.slice(0, 12)
  const ciphertext = combined.slice(12)
  
  const decrypted = await crypto.subtle.decrypt(
    { name: 'AES-GCM', iv },
    key,
    ciphertext
  )
  
  return new Uint8Array(decrypted)
}

async function deriveKey(salt: Uint8Array): Promise<CryptoKey> {
  // Usa un segreto fisso dell'app + salt per utente
  // In produzione, potresti usare il credential ID della passkey
  const secret = new TextEncoder().encode('ecothread-wallet-key-v1')
  
  const keyMaterial = await crypto.subtle.importKey(
    'raw', secret, 'PBKDF2', false, ['deriveBits', 'deriveKey']
  )
  
  return crypto.subtle.deriveKey(
    { name: 'PBKDF2', salt, iterations: 100000, hash: 'SHA-256' },
    keyMaterial,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt', 'decrypt']
  )
}

function bufferToBase64(buffer: Uint8Array): string {
  return btoa(String.fromCharCode(...buffer))
}

function base64ToBuffer(base64: string): Uint8Array {
  return new Uint8Array(atob(base64).split('').map(c => c.charCodeAt(0)))
}