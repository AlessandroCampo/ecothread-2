// resources/js/composables/useEmbeddedWallet.ts

import { ref, computed } from 'vue'
import { Keypair } from '@solana/web3.js'
import * as bip39 from 'bip39'
import { derivePath } from 'ed25519-hd-key'
import nacl from 'tweetnacl'
import { encode as encodeBase58, decode as decodeBase58 } from 'bs58'
import api from '@/lib/axios'

// Stato globale del wallet
const walletAddress = ref<string | null>(null)
const isWalletReady = ref(false)
const isPasskeySupported = ref(false)

// Chiave decriptata temporaneamente in memoria (mai persistita)
let decryptedKeypair: Keypair | null = null

export function useWallet() {
  
  // ============================================
  // INIT: Controlla supporto WebAuthn
  // ============================================
  const init = async () => {
    isPasskeySupported.value = !!(
      window.PublicKeyCredential &&
      typeof window.PublicKeyCredential === 'function'
    )
    
    // Carica wallet esistente se loggato
    try {
      const { data } = await api.get('/wallet/encrypted-key')
      walletAddress.value = data.wallet_address
      isWalletReady.value = true
    } catch (e) {
      // Nessun wallet configurato
      isWalletReady.value = false
    }
  }

  // ============================================
  // GENERA NUOVO WALLET
  // ============================================
  const generateWallet = async (): Promise<{
    mnemonic: string
    publicKey: string
  }> => {
    // 1. Genera mnemonic BIP39
    const mnemonic = bip39.generateMnemonic(256) // 24 parole
    
    // 2. Deriva keypair Solana (path standard)
    const seed = await bip39.mnemonicToSeed(mnemonic)
    const derivedSeed = derivePath("m/44'/501'/0'/0'", seed.toString('hex')).key
    const keypair = Keypair.fromSeed(derivedSeed)
    
    // 3. Tieni in memoria temporaneamente
    decryptedKeypair = keypair
    
    return {
      mnemonic,
      publicKey: keypair.publicKey.toBase58()
    }
  }

  // ============================================
  // REGISTRA PASSKEY E SALVA WALLET
  // ============================================
  const registerPasskeyAndSave = async (publicKey: string): Promise<boolean> => {
    if (!decryptedKeypair) {
      throw new Error('Genera prima il wallet')
    }
    
    try {
      // 1. Crea credenziale WebAuthn
      const challenge = crypto.getRandomValues(new Uint8Array(32))
      
      const credential = await navigator.credentials.create({
        publicKey: {
          challenge,
          rp: {
            name: 'EcoThread',
            id: window.location.hostname
          },
          user: {
            id: new TextEncoder().encode(publicKey),
            name: publicKey.slice(0, 8) + '...',
            displayName: 'EcoThread Wallet'
          },
          pubKeyCredParams: [
            { alg: -7, type: 'public-key' },   // ES256
            { alg: -257, type: 'public-key' }  // RS256
          ],
          authenticatorSelection: {
            authenticatorAttachment: 'platform', // Forza passkey del dispositivo
            userVerification: 'required',
            residentKey: 'required'
          },
          timeout: 60000
        }
      }) as PublicKeyCredential
      
      // 2. Usa la credenziale per derivare una chiave di encryption
      const credentialId = encodeBase58(new Uint8Array(credential.rawId))
      
      // 3. Cripta la private key
      // Usiamo l'ID della credenziale + un salt come base per la chiave AES
      const encryptionKey = await deriveEncryptionKey(credential.rawId)
      const encryptedPrivateKey = await encryptPrivateKey(
        decryptedKeypair.secretKey,
        encryptionKey
      )
      
      // 4. Salva sul backend
      await api.post('/wallet', {
        wallet_address: publicKey,
        encrypted_private_key: encryptedPrivateKey,
        passkey_credential_id: credentialId
      })
      
      walletAddress.value = publicKey
      isWalletReady.value = true
      
      return true
    } catch (e) {
      console.error('Passkey registration failed:', e)
      throw e
    }
  }

  // ============================================
  // DECRIPTA WALLET CON PASSKEY (per firmare)
  // ============================================
  const unlockWallet = async (): Promise<Keypair> => {
    if (decryptedKeypair) {
      return decryptedKeypair
    }
    
    // 1. Recupera dati dal backend
    const { data } = await api.get('/wallet/encrypted-key')
    
    // 2. Richiedi autenticazione passkey
    const credentialId = decodeBase58(data.passkey_credential_id)
    
    const assertion = await navigator.credentials.get({
      publicKey: {
        challenge: crypto.getRandomValues(new Uint8Array(32)),
        allowCredentials: [{
          id: credentialId,
          type: 'public-key'
        }],
        userVerification: 'required',
        timeout: 60000
      }
    }) as PublicKeyCredential
    
    // 3. Deriva chiave di decryption
    const encryptionKey = await deriveEncryptionKey(assertion.rawId)
    
    // 4. Decripta
    const privateKey = await decryptPrivateKey(
      data.encrypted_private_key,
      encryptionKey
    )
    
    decryptedKeypair = Keypair.fromSecretKey(privateKey)
    
    return decryptedKeypair
  }

  // ============================================
  // FIRMA MESSAGGIO
  // ============================================
  const signMessage = async (message: Uint8Array): Promise<Uint8Array> => {
    const keypair = await unlockWallet()
    return nacl.sign.detached(message, keypair.secretKey)
  }

  // ============================================
  // FIRMA EVENTO PER BLOCKCHAIN
  // ============================================
  const signEvent = async (eventData: {
    product_id: string
    event_type: string
    data_hash: string
    timestamp: number
  }): Promise<{
    message: Uint8Array
    signature: string
    publicKey: string
  }> => {
    const keypair = await unlockWallet()
    
    const message = new TextEncoder().encode(JSON.stringify(eventData))
    const signature = nacl.sign.detached(message, keypair.secretKey)
    
    return {
      message,
      signature: encodeBase58(signature),
      publicKey: keypair.publicKey.toBase58()
    }
  }

  // ============================================
  // RECOVERY DA MNEMONIC
  // ============================================
  const recoverFromMnemonic = async (mnemonic: string): Promise<string> => {
    if (!bip39.validateMnemonic(mnemonic)) {
      throw new Error('Recovery phrase non valida')
    }
    
    const seed = await bip39.mnemonicToSeed(mnemonic)
    const derivedSeed = derivePath("m/44'/501'/0'/0'", seed.toString('hex')).key
    const keypair = Keypair.fromSeed(derivedSeed)
    
    decryptedKeypair = keypair
    
    return keypair.publicKey.toBase58()
  }

  // ============================================
  // LOCK WALLET (pulisci dalla memoria)
  // ============================================
  const lockWallet = () => {
    decryptedKeypair = null
  }

  // ============================================
  // HELPERS: Encryption
  // ============================================
  const deriveEncryptionKey = async (credentialId: ArrayBuffer): Promise<CryptoKey> => {
    const keyMaterial = await crypto.subtle.importKey(
      'raw',
      credentialId,
      'PBKDF2',
      false,
      ['deriveBits', 'deriveKey']
    )
    
    // Salt fisso per app (in produzione: salt per-user salvato nel DB)
    const salt = new TextEncoder().encode('ecothread-wallet-v1')
    
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

  const encryptPrivateKey = async (
    privateKey: Uint8Array,
    encryptionKey: CryptoKey
  ): Promise<string> => {
    const iv = crypto.getRandomValues(new Uint8Array(12))
    
    const encrypted = await crypto.subtle.encrypt(
      { name: 'AES-GCM', iv },
      encryptionKey,
      privateKey
    )
    
    // Combina IV + ciphertext e codifica in base64
    const combined = new Uint8Array(iv.length + encrypted.byteLength)
    combined.set(iv)
    combined.set(new Uint8Array(encrypted), iv.length)
    
    return btoa(String.fromCharCode(...combined))
  }

  const decryptPrivateKey = async (
    encryptedData: string,
    encryptionKey: CryptoKey
  ): Promise<Uint8Array> => {
    const combined = new Uint8Array(
      atob(encryptedData).split('').map(c => c.charCodeAt(0))
    )
    
    const iv = combined.slice(0, 12)
    const ciphertext = combined.slice(12)
    
    const decrypted = await crypto.subtle.decrypt(
      { name: 'AES-GCM', iv },
      encryptionKey,
      ciphertext
    )
    
    return new Uint8Array(decrypted)
  }

  return {
    // State
    walletAddress: computed(() => walletAddress.value),
    isWalletReady: computed(() => isWalletReady.value),
    isPasskeySupported: computed(() => isPasskeySupported.value),
    
    // Methods
    init,
    generateWallet,
    registerPasskeyAndSave,
    unlockWallet,
    signMessage,
    signEvent,
    recoverFromMnemonic,
    lockWallet
  }
}