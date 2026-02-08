/**
 * usePasskeyAuth - Autenticazione passkey + wallet Solana
 *
 * Librerie:
 * - @simplewebauthn/browser per WebAuthn (web)
 * - capacitor-passkey-plugin per WebAuthn nativo (Android/iOS)
 * - @scure/bip39 per mnemonic (browser-native)
 * - @solana/web3.js per keypair
 */

import { ref, computed, readonly } from 'vue'
import { startRegistration, startAuthentication } from '@simplewebauthn/browser'
import { PasskeyPlugin } from 'capacitor-passkey-plugin'
import { generateMnemonic, mnemonicToSeedSync, validateMnemonic } from '@scure/bip39'
import { wordlist } from '@scure/bip39/wordlists/english.js'
import { Keypair, PublicKey } from '@solana/web3.js'
import nacl from 'tweetnacl'
import api from '@/lib/axios'

// ============================================
// PLATFORM DETECTION
// ============================================

const isNative = (): boolean => !!(window as any).Capacitor?.isNativePlatform()

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

interface RecoveryFileData {
  version: number
  type: 'ecothread-wallet-backup'
  company_name: string
  wallet_address: string
  created_at: string
  encrypted: string
  salt: string
  iv: string
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
let tempRecoveryCode: string | null = null
let tempPublicKey: string | null = null

// ============================================
// HELPERS (encoding)
// ============================================

function bufferToBase64(buffer: Uint8Array): string {
  return btoa(String.fromCharCode(...buffer))
}

function base64ToBuffer(base64: string): Uint8Array {
  return new Uint8Array(atob(base64).split('').map(c => c.charCodeAt(0)))
}

// ============================================
// HELPERS (wallet encryption - passkey based)
// ============================================

async function deriveKey(salt: Uint8Array): Promise<CryptoKey> {
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

async function encryptPrivateKey(privateKey: Uint8Array, salt: Uint8Array): Promise<string> {
  const key = await deriveKey(salt)
  const iv = crypto.getRandomValues(new Uint8Array(12))
  
  const encrypted = await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv },
    key,
    privateKey
  )

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

// ============================================
// HELPERS (recovery file encryption - code based)
// ============================================

function generateRecoveryCode(): string {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789' // no 0,O,1,I,L
  const segments: string[] = []
  
  for (let s = 0; s < 4; s++) {
    let segment = ''
    for (let i = 0; i < 4; i++) {
      segment += chars[Math.floor(Math.random() * chars.length)]
    }
    segments.push(segment)
  }
  
  return segments.join('-') // es: "ECOT-X7K9-M2NP-VQ4R"
}

async function deriveKeyFromCode(code: string, salt: Uint8Array): Promise<CryptoKey> {
  const encoder = new TextEncoder()
  const normalizedCode = code.replace(/-/g, '').toUpperCase()
  
  const keyMaterial = await crypto.subtle.importKey(
    'raw',
    encoder.encode(normalizedCode),
    'PBKDF2',
    false,
    ['deriveKey']
  )
  
  return crypto.subtle.deriveKey(
    {
      name: 'PBKDF2',
      salt,
      iterations: 100000,
      hash: 'SHA-256'
    },
    keyMaterial,
    { name: 'AES-GCM', length: 256 },
    false,
    ['encrypt', 'decrypt']
  )
}

async function encryptMnemonic(
  mnemonic: string, 
  recoveryCode: string
): Promise<{ encrypted: string; salt: string; iv: string }> {
  const encoder = new TextEncoder()
  const salt = crypto.getRandomValues(new Uint8Array(16))
  const iv = crypto.getRandomValues(new Uint8Array(12))
  
  const key = await deriveKeyFromCode(recoveryCode, salt)
  
  const encrypted = await crypto.subtle.encrypt(
    { name: 'AES-GCM', iv },
    key,
    encoder.encode(mnemonic)
  )
  
  return {
    encrypted: bufferToBase64(new Uint8Array(encrypted)),
    salt: bufferToBase64(salt),
    iv: bufferToBase64(iv)
  }
}

async function decryptMnemonic(
  encryptedData: { encrypted: string; salt: string; iv: string },
  recoveryCode: string
): Promise<string> {
  const decoder = new TextDecoder()
  
  const salt = base64ToBuffer(encryptedData.salt)
  const iv = base64ToBuffer(encryptedData.iv)
  const encrypted = base64ToBuffer(encryptedData.encrypted)
  
  const key = await deriveKeyFromCode(recoveryCode, salt)
  
  const decrypted = await crypto.subtle.decrypt(
    { name: 'AES-GCM', iv },
    key,
    encrypted
  )
  
  return decoder.decode(decrypted)
}

// ============================================
// COMPOSABLE
// ============================================

export function usePasskeyAuth() {

  const isPasskeySupported = computed(() => {
    if ((window as any).Capacitor?.isNativePlatform()) {
      return true
    }
    return !!window.PublicKeyCredential
  })

  // ==========================================
  // GENERATE WALLET
  // ==========================================

  const generateWallet = async (): Promise<{ publicKey: string; recoveryCode: string }> => {
    // Genera mnemonic con @scure/bip39
    const mnemonic = generateMnemonic(wordlist, 256) // 24 parole
    
    // Deriva seed
    const seed = mnemonicToSeedSync(mnemonic)
    
    // Usa i primi 32 byte per Solana keypair
    const keypair = Keypair.fromSeed(seed.slice(0, 32))
    
    // Genera recovery code
    const recoveryCode = generateRecoveryCode()
    
    // Salva temporaneamente
    tempMnemonic = mnemonic
    tempKeypair = keypair
    tempRecoveryCode = recoveryCode
    tempPublicKey = keypair.publicKey.toBase58()
    
    return {
      publicKey: tempPublicKey,
      recoveryCode
    }
  }

  // ==========================================
  // CREATE RECOVERY FILE
  // ==========================================

  const createRecoveryFile = async (companyName: string): Promise<Blob> => {
    if (!tempMnemonic || !tempRecoveryCode || !tempPublicKey) {
      throw new Error('Wallet non generato')
    }
    
    const { encrypted, salt, iv } = await encryptMnemonic(tempMnemonic, tempRecoveryCode)
    
    const recoveryData: RecoveryFileData = {
      version: 1,
      type: 'ecothread-wallet-backup',
      company_name: companyName,
      wallet_address: tempPublicKey,
      created_at: new Date().toISOString(),
      encrypted,
      salt,
      iv
    }
    
    return new Blob(
      [JSON.stringify(recoveryData, null, 2)],
      { type: 'application/json' }
    )
  }

  // ==========================================
  // RECOVER FROM FILE
  // ==========================================

  const recoverFromFile = async (file: File, recoveryCode: string): Promise<string> => {
    try {
      const content = await file.text()
      const data = JSON.parse(content) as RecoveryFileData
      
      if (data.type !== 'ecothread-wallet-backup') {
        throw new Error('File non valido')
      }
      
      const mnemonic = await decryptMnemonic(
        { encrypted: data.encrypted, salt: data.salt, iv: data.iv },
        recoveryCode
      )
      
      // Valida che il mnemonic sia corretto
      if (!validateMnemonic(mnemonic, wordlist)) {
        throw new Error('Codice di recupero non valido')
      }
      
      // Ricrea il keypair
      const seed = mnemonicToSeedSync(mnemonic)
      const keypair = Keypair.fromSeed(seed.slice(0, 32))
      
      // Verifica che l'indirizzo corrisponda
      if (keypair.publicKey.toBase58() !== data.wallet_address) {
        throw new Error('Codice di recupero non valido')
      }
      
      // Salva per la registrazione
      tempKeypair = keypair
      tempMnemonic = mnemonic
      tempPublicKey = data.wallet_address
      
      return data.wallet_address
    } catch (e: any) {
      if (e.name === 'OperationError') {
        throw new Error('Codice di recupero non valido')
      }
      throw e
    }
  }

  // ==========================================
  // REGISTER
  // ==========================================

  const register = async (name: string, email?: string): Promise<boolean> => {
    if (!tempKeypair) {
      throw new Error('Genera prima il wallet')
    }

    isLoading.value = true
    error.value = null

    try {
      // 1. Ottieni opzioni dal server
      const { data: options } = await api.post('/auth/register/options', {
        name,
        email,
        wallet_address: tempKeypair.publicKey.toBase58(),
      })

      // 2. Crea passkey (nativo o web)
      let credential: any
      if (isNative()) {
        const result = await PasskeyPlugin.createPasskey({ publicKey: options })
        credential = { ...result, type: 'public-key' }
      } else {
        credential = await startRegistration({ optionsJSON: options })
      }

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
        
        // Pulisci dati temporanei
        tempMnemonic = null
        tempKeypair = null
        tempRecoveryCode = null
        tempPublicKey = null
        
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

      // 2. Autentica (nativo o web)
      let credential: any
      if (isNative()) {
        credential = await PasskeyPlugin.authenticate({ publicKey: options })
      } else {
        credential = await startAuthentication({ optionsJSON: options })
      }

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
      if (isNative()) {
        await PasskeyPlugin.authenticate({ publicKey: options })
      } else {
        await startAuthentication({ optionsJSON: options })
      }

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
    
    const signature = nacl.sign.detached(messageBytes, decryptedKeypair!.secretKey)

    return {
      message,
      signature: bufferToBase64(signature),
      publicKey: decryptedKeypair!.publicKey.toBase58(),
    }
  }

  const signTransaction = async (messageBytes: Uint8Array): Promise<{
    signature: string
    publicKey: string
  }> => {
    if (!decryptedKeypair) {
      const unlocked = await unlockWallet()
      if (!unlocked) throw new Error('Wallet non sbloccato')
    }

    const signature = nacl.sign.detached(messageBytes, decryptedKeypair!.secretKey)

    return {
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
  // RECOVERY (mnemonic diretto - legacy)
  // ==========================================

  const recoverFromMnemonic = async (mnemonic: string): Promise<string> => {
    if (!validateMnemonic(mnemonic, wordlist)) {
      throw new Error('Recovery phrase non valida')
    }

    const seed = mnemonicToSeedSync(mnemonic)
    const keypair = Keypair.fromSeed(seed.slice(0, 32))

    tempKeypair = keypair
    tempPublicKey = keypair.publicKey.toBase58()
    
    return tempPublicKey
  }

  // ==========================================
  // UTILITIES
  // ==========================================

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
      isWalletUnlocked.value = false
    }
  }

  const clearTempData = () => {
    tempMnemonic = null
    tempKeypair = null
    tempRecoveryCode = null
    tempPublicKey = null
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
    isLoading,
    error,
    isPasskeySupported,
    
    // Auth
    generateWallet,
    register,
    login,
    checkSession,
    logout,
    initFromUser,
    
    // Wallet
    unlockWallet,
    lockWallet,
    signEvent,
    signTransaction,
    getPublicKey,
    
    // Recovery - file based (nuovo)
    createRecoveryFile,
    recoverFromFile,
    getTempRecoveryCode: () => tempRecoveryCode,
    getTempPublicKey: () => tempPublicKey,
    
    // Recovery - mnemonic based (legacy)
    recoverFromMnemonic,
    getTempMnemonic: () => tempMnemonic,
    
    // Cleanup
    clearTempMnemonic: clearTempData,
    clearTempRecoveryCode: clearTempData,
    clearTempData,
  }
}