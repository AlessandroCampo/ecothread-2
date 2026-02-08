/**
 * useSolana - Operazioni Solana con wallet passkey
 * 
 * Firma: wallet azienda (passkey)
 * Fee: wallet EcoThread (Laravel backend)
 */

import { ref, computed } from 'vue'
import { 
  Connection, 
  PublicKey, 
  Transaction, 
  SystemProgram,
} from '@solana/web3.js'
import { Program, BN, Idl } from '@coral-xyz/anchor'
import { usePasskeyAuth } from './usePasskeyAuth'
import api from '@/lib/axios'
import idlJson from '../idl/ecothread.json'

const idl = idlJson as Idl

// ============================================
// Configuration
// ============================================
const RPC_ENDPOINTS = [
  import.meta.env.VITE_SOLANA_NETWORK,
  'https://api.devnet.solana.com'
].filter(Boolean) as string[]

const SOLANA_CLUSTER = 'devnet'
const PROGRAM_ID = new PublicKey(
  (idlJson as any).address || import.meta.env.VITE_SOLANA_PROGRAM_ID
)

export function useSolana() {
  const connection = new Connection(RPC_ENDPOINTS[0], 'confirmed')
  
  const loading = ref(false)
  const error = ref<string | null>(null)
  const lastTxSignature = ref<string | null>(null)
  const feePayerPublicKey = ref<string | null>(null)
  const isReady = ref(false)

  // ============================================
  // Passkey Auth
  // ============================================
  const { 
    user,
    isAuthenticated,
    isWalletUnlocked,
    walletAddress,
    signTransaction,
    unlockWallet,
    getPublicKey,
  } = usePasskeyAuth()

  const isWalletConnected = computed(() => isAuthenticated.value && !!walletAddress.value)

  // ============================================
  // Fetch Fee Payer Info (from Laravel)
  // ============================================
  const fetchFeePayer = async (): Promise<string | null> => {
    try {
      const { data } = await api.get('/api/solana/fee-payer')
      feePayerPublicKey.value = data.publicKey
      return data.publicKey
    } catch (e) {
      console.error('Failed to fetch fee payer:', e)
      return null
    }
  }

  // ============================================
  // PDA Derivation
  // ============================================
  const getProductPDA = (productId: string): PublicKey => {
    const [pda] = PublicKey.findProgramAddressSync(
      [Buffer.from('product'), Buffer.from(productId)],
      PROGRAM_ID
    )
    return pda
  }

  const getEventPDA = (productPda: PublicKey, eventIndex: number): PublicKey => {
    const [pda] = PublicKey.findProgramAddressSync(
      [
        Buffer.from('event'),
        productPda.toBuffer(),
        new BN(eventIndex).toArrayLike(Buffer, 'le', 4),
      ],
      PROGRAM_ID
    )
    return pda
  }

  // ============================================
  // Get Anchor Program (read-only)
  // ============================================
  const getReadOnlyProgram = () => {
    const readOnlyProvider = {
      connection,
      publicKey: null,
    }
    return new Program(idl, readOnlyProvider as any)
  }

  // ============================================
  // Create Product
  // ============================================
  const createProduct = async (productId: string): Promise<{
    success: boolean
    txSignature?: string
    pdaAddress?: string
    error?: string
  }> => {
    loading.value = true
    error.value = null

    try {
      if (!isWalletConnected.value || !walletAddress.value) {
        throw new Error('Wallet non connesso')
      }

      // Sblocca wallet se necessario
      if (!isWalletUnlocked.value) {
        const unlocked = await unlockWallet()
        if (!unlocked) throw new Error('Sblocco wallet annullato')
      }

      // Ottieni fee payer da Laravel
      const feePayer = await fetchFeePayer()
      if (!feePayer) throw new Error('Fee payer non disponibile')

      const creatorPubkey = new PublicKey(walletAddress.value)
      const feePayerPubkey = new PublicKey(feePayer)
      const productPDA = getProductPDA(productId)

      console.log('📦 Creating product:', {
        productId,
        pda: productPDA.toBase58(),
        creator: walletAddress.value,
        feePayer,
      })

      // Costruisci la transazione usando Anchor
      const program = getReadOnlyProgram()
      
      // Ottieni blockhash recente
      const { blockhash, lastValidBlockHeight } = await connection.getLatestBlockhash()

      // Costruisci l'istruzione
     const instruction = await program.methods
      .createProduct(productId)
      .accounts({
        product: productPDA,
        creator: creatorPubkey,
        feePayer: feePayerPubkey,  // ← Aggiungi
        systemProgram: SystemProgram.programId,
      })
      .instruction()
      // Crea la transazione con fee payer EcoThread
      const transaction = new Transaction({
        feePayer: feePayerPubkey,
        blockhash,
        lastValidBlockHeight,
      }).add(instruction)

      // Serializza il messaggio per la firma
      const message = transaction.serializeMessage()

      // Firma con il wallet passkey
      const { signature, publicKey } = await signTransaction(message)

      console.log('✍️ Signed by:', publicKey)

      // Invia a Laravel per aggiungere firma fee payer e broadcast
      const { data: result } = await api.post('/api/solana/sign-and-submit', {
        transaction: Buffer.from(transaction.serialize({ requireAllSignatures: false })).toString('base64'),
        signerPublicKey: publicKey,
        signerSignature: signature,
      })

      if (!result.success) {
        throw new Error(result.error || 'Errore invio transazione')
      }

      lastTxSignature.value = result.txSignature

      console.log('✅ Product created:', {
        txSignature: result.txSignature,
        pdaAddress: productPDA.toBase58(),
      })

      return {
        success: true,
        txSignature: result.txSignature,
        pdaAddress: productPDA.toBase58(),
      }
    } catch (e: any) {
      console.error('❌ Error creating product:', e)
      const errorMessage = parseError(e)
      error.value = errorMessage
      return { success: false, error: errorMessage }
    } finally {
      loading.value = false
    }
  }

  // ============================================
  // Add Event
  // ============================================
  const addEvent = async (
    productId: string,
    eventIndex: number,
    eventType: string,
    documentHash: string,
    documentUri: string = '',
    metadataHash: string = ''
  ): Promise<{
    success: boolean
    txSignature?: string
    pdaAddress?: string
    error?: string
  }> => {
    loading.value = true
    error.value = null

    try {
      if (!isWalletConnected.value || !walletAddress.value) {
        throw new Error('Wallet non connesso')
      }

      if (!isWalletUnlocked.value) {
        const unlocked = await unlockWallet()
        if (!unlocked) throw new Error('Sblocco wallet annullato')
      }

      const feePayer = await fetchFeePayer()
      if (!feePayer) throw new Error('Fee payer non disponibile')

      const registrantPubkey = new PublicKey(walletAddress.value)
      const feePayerPubkey = new PublicKey(feePayer)
      const productPDA = getProductPDA(productId)
      const eventPDA = getEventPDA(productPDA, eventIndex)

      const finalDocumentHash = documentHash || '0'.repeat(64)
      const finalMetadataHash = metadataHash || '0'.repeat(64)

      console.log('📝 Adding event:', {
        productId,
        eventIndex,
        eventType,
        eventPda: eventPDA.toBase58(),
      })

      const program = getReadOnlyProgram()
      const { blockhash, lastValidBlockHeight } = await connection.getLatestBlockhash()

      const instruction = await program.methods
        .addEvent(eventType, finalDocumentHash, documentUri, finalMetadataHash)
        .accounts({
          product: productPDA,
          event: eventPDA,
          registrant: registrantPubkey,
          feePayer: feePayerPubkey,  // ← Aggiungi
          systemProgram: SystemProgram.programId,
        })
        .instruction()

      const transaction = new Transaction({
        feePayer: feePayerPubkey,
        blockhash,
        lastValidBlockHeight,
      }).add(instruction)

      const message = transaction.serializeMessage()
      const { signature, publicKey } = await signTransaction(message)

      const { data: result } = await api.post('/api/solana/sign-and-submit', {
        transaction: Buffer.from(transaction.serialize({ requireAllSignatures: false })).toString('base64'),
        signerPublicKey: publicKey,
        signerSignature: signature,
      })

      if (!result.success) {
        throw new Error(result.error || 'Errore invio transazione')
      }

      lastTxSignature.value = result.txSignature

      console.log('✅ Event added:', {
        txSignature: result.txSignature,
        pdaAddress: eventPDA.toBase58(),
      })

      return {
        success: true,
        txSignature: result.txSignature,
        pdaAddress: eventPDA.toBase58(),
      }
    } catch (e: any) {
      console.error('❌ Error adding event:', e)
      const errorMessage = parseError(e)
      error.value = errorMessage
      return { success: false, error: errorMessage }
    } finally {
      loading.value = false
    }
  }

  // ============================================
  // Fetch Product (read-only)
  // ============================================
  const fetchProduct = async (productId: string) => {
    try {
      const program = getReadOnlyProgram()
      const productPDA = getProductPDA(productId)
      const product = await program.account.product.fetch(productPDA)
      
      return {
        success: true,
        data: {
          productId: (product as any).productId,
          creator: (product as any).creator.toBase58(),
          createdAt: (product as any).createdAt.toNumber(),
          eventCount: (product as any).eventCount,
          pdaAddress: productPDA.toBase58(),
        }
      }
    } catch (e: any) {
      return { success: false, error: e.message }
    }
  }

  // ============================================
  // Fetch Event (read-only)
  // ============================================
  const fetchEvent = async (pdaAddress: string) => {
    try {
      const program = getReadOnlyProgram()
      const pda = new PublicKey(pdaAddress)
      const event = await program.account.event.fetch(pda)
      
      return {
        success: true,
        data: {
          product: (event as any).product.toBase58(),
          eventIndex: (event as any).eventIndex,
          eventType: (event as any).eventType,
          registrant: (event as any).registrant.toBase58(),
          timestamp: (event as any).timestamp.toNumber(),
          documentHash: (event as any).documentHash,
          documentUri: (event as any).documentUri,
          metadataHash: (event as any).metadataHash,
        }
      }
    } catch (e: any) {
      return { success: false, error: e.message }
    }
  }

  // ============================================
  // Hash Utilities
  // ============================================
  const hashFile = async (file: File): Promise<string> => {
    const buffer = await file.arrayBuffer()
    const hashBuffer = await crypto.subtle.digest('SHA-256', buffer)
    const hashArray = Array.from(new Uint8Array(hashBuffer))
    return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('')
  }

  const hashMetadata = async (metadata: Record<string, any>): Promise<string> => {
    if (!metadata || Object.keys(metadata).length === 0) {
      return '0'.repeat(64)
    }
    const sortedMetadata = JSON.stringify(metadata, Object.keys(metadata).sort())
    const encoder = new TextEncoder()
    const data = encoder.encode(sortedMetadata)
    const hashBuffer = await crypto.subtle.digest('SHA-256', data)
    const hashArray = Array.from(new Uint8Array(hashBuffer))
    return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('')
  }

  // ============================================
  // Explorer URLs
  // ============================================
  const getTxExplorerUrl = (signature: string): string => {
    return `https://explorer.solana.com/tx/${signature}?cluster=${SOLANA_CLUSTER}`
  }

  const getAddressExplorerUrl = (address: string): string => {
    return `https://explorer.solana.com/address/${address}?cluster=${SOLANA_CLUSTER}`
  }

  // ============================================
  // Balance
  // ============================================
  const getBalance = async (): Promise<number | null> => {
    if (!walletAddress.value) return null
    try {
      const pubkey = new PublicKey(walletAddress.value)
      const balance = await connection.getBalance(pubkey)
      return balance / 1e9
    } catch {
      return null
    }
  }

  const whenReady = async (): Promise<void> => {
  // Verifica che la connessione Solana sia attiva
  try {
    await connection.getLatestBlockhash()
    isReady.value = true
  } catch (e) {
    console.warn('Solana connection not ready:', e)
    // Riprova con endpoint fallback
    for (const endpoint of RPC_ENDPOINTS.slice(1)) {
      try {
        const fallbackConnection = new Connection(endpoint, 'confirmed')
        await fallbackConnection.getLatestBlockhash()
        isReady.value = true
        return
      } catch {
        continue
      }
    }
    throw new Error('Impossibile connettersi a Solana')
  }
}


  // ============================================
  // Error Parser
  // ============================================
  const parseError = (e: any): string => {
    const message = e.response?.data?.error || e.message || 'Unknown error'

    if (message.includes('Wallet non connesso') || message.includes('not connected')) {
      return 'Effettua il login per continuare'
    }
    if (message.includes('annullato') || message.includes('rejected')) {
      return 'Operazione annullata'
    }
    if (message.includes('insufficient funds') || message.includes('Insufficient')) {
      return 'Fondi insufficienti nel fee payer'
    }
    if (message.includes('already in use') || message.includes('already exists')) {
      return 'Questo ID esiste già on-chain'
    }
    if (message.includes('Invalid signer signature')) {
      return 'Firma non valida'
    }
    if (message.includes('Fee payer non disponibile')) {
      return 'Servizio temporaneamente non disponibile'
    }

    return message.length > 100 ? message.slice(0, 100) + '...' : message
  }

  // ============================================
  // Return
  // ============================================
  return {
    // State
    loading,
    error,
    lastTxSignature,
    feePayerPublicKey,
    
    // Wallet
    walletAddress,
    isWalletConnected,
    isWalletUnlocked,
    unlockWallet,
    
    // Core methods
    createProduct,
    addEvent,
    fetchProduct,
    fetchEvent,
    
    // Hash utilities
    hashFile,
    hashMetadata,
    
    // PDA utilities
    getProductPDA,
    getEventPDA,
    
    // Balance
    getBalance,
    
    // Fee payer
    fetchFeePayer,
    
    // Explorer URLs
    getTxExplorerUrl,
    getAddressExplorerUrl,
    isReady,
  whenReady,
  }
}
