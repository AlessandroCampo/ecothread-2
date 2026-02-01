import { ref, computed } from 'vue'
import { Connection, PublicKey, SystemProgram } from '@solana/web3.js'
import { Program, AnchorProvider, BN, Idl } from '@coral-xyz/anchor'
import { useWallet } from 'solana-wallets-vue'
import idlJson from '../idl/ecothread.json'

const idl = idlJson as Idl

// ============================================
// Configuration
// ============================================
const PROGRAM_ID = new PublicKey(
  (idlJson as any).address || import.meta.env.VITE_SOLANA_PROGRAM_ID
)
const NETWORK = import.meta.env.VITE_SOLANA_NETWORK || 'https://api.devnet.solana.com'
const SOLANA_CLUSTER = 'devnet'

export function useSolana() {
  const connection = new Connection(NETWORK, 'confirmed')
  const loading = ref(false)
  const error = ref<string | null>(null)
  const lastTxSignature = ref<string | null>(null)
  const isReady = ref(false)
  const connectionError = ref<string | null>(null)

  // ============================================
  // Wallet Adapter
  // ============================================
  const { 
    publicKey, 
    connected, 
    wallet,
    signMessage,
    connect,
    connecting,
    wallets,
    select,
  } = useWallet()

  const isWalletConnected = computed(() => connected.value && !!publicKey.value)

  // ============================================
  // Provider & Program
  // ============================================
  const getAnchorProvider = () => {
    if (!wallet.value?.adapter || !publicKey.value) {
      throw new Error('Wallet not connected')
    }
    
    return new AnchorProvider(
      connection,
      wallet.value.adapter as any,
      { commitment: 'confirmed' }
    )
  }

  const getProgram = () => {
    const provider = getAnchorProvider()
    return new Program(idl, provider)
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
      if (!isWalletConnected.value || !publicKey.value) {
        throw new Error('Wallet not connected')
      }

      const program = getProgram()
      const productPDA = getProductPDA(productId)

      console.log('📦 Creating product on-chain:', {
        productId,
        pda: productPDA.toBase58(),
        creator: publicKey.value.toBase58(),
      })

      const tx = await program.methods
        .createProduct(productId)
        .accounts({
          product: productPDA,
          creator: publicKey.value,
          systemProgram: SystemProgram.programId,
        })
        .rpc()

      await connection.confirmTransaction(tx, 'confirmed')
      
      lastTxSignature.value = tx

      console.log('✅ Product created:', {
        txSignature: tx,
        pdaAddress: productPDA.toBase58(),
      })

      return {
        success: true,
        txSignature: tx,
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
      if (!isWalletConnected.value || !publicKey.value) {
        throw new Error('Wallet not connected')
      }

      const program = getProgram()
      const productPDA = getProductPDA(productId)
      const eventPDA = getEventPDA(productPDA, eventIndex)

      // Default empty hashes to 64 zeros if not provided
      const finalDocumentHash = documentHash || '0'.repeat(64)
      const finalMetadataHash = metadataHash || '0'.repeat(64)

      console.log('📝 Adding event on-chain:', {
        productId,
        eventIndex,
        eventType,
        documentHash: finalDocumentHash.slice(0, 16) + '...',
        metadataHash: finalMetadataHash.slice(0, 16) + '...',
        eventPda: eventPDA.toBase58(),
      })

      const tx = await program.methods
        .addEvent(eventType, finalDocumentHash, documentUri, finalMetadataHash)
        .accounts({
          product: productPDA,
          event: eventPDA,
          registrant: publicKey.value,
          systemProgram: SystemProgram.programId,
        })
        .rpc()

      await connection.confirmTransaction(tx, 'confirmed')
      
      lastTxSignature.value = tx

      console.log('✅ Event added:', {
        txSignature: tx,
        pdaAddress: eventPDA.toBase58(),
      })

      return {
        success: true,
        txSignature: tx,
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
  // Fetch Product
  // ============================================
  const fetchProduct = async (productId: string) => {
    try {
      const program = getProgram()
      const productPDA = getProductPDA(productId)
      const product = await program.account.product.fetch(productPDA)
      
      return {
        success: true,
        data: {
          productId: product.productId,
          creator: product.creator.toBase58(),
          createdAt: product.createdAt.toNumber(),
          eventCount: product.eventCount,
          pdaAddress: productPDA.toBase58(),
        }
      }
    } catch (e: any) {
      return { success: false, error: e.message }
    }
  }

  // ============================================
  // Fetch Event
  // ============================================
  const fetchEvent = async (pdaAddress: string) => {
    try {
      const program = getProgram()
      const pda = new PublicKey(pdaAddress)
      
      const event = await program.account.event.fetch(pda)
      
      return {
        success: true,
        data: {
          product: event.product.toBase58(),
          eventIndex: event.eventIndex,
          eventType: event.eventType,
          registrant: event.registrant.toBase58(),
          timestamp: event.timestamp.toNumber(),
          documentHash: event.documentHash,
          documentUri: event.documentUri,
          metadataHash: event.metadataHash,
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
    
    // Sort keys for consistent hashing
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
  // Airdrop (devnet only)
  // ============================================
  const requestAirdrop = async (amount: number = 1): Promise<boolean> => {
    if (!publicKey.value) {
      error.value = 'Wallet not connected'
      return false
    }

    try {
      loading.value = true
      const signature = await connection.requestAirdrop(
        publicKey.value,
        amount * 1e9
      )
      await connection.confirmTransaction(signature, 'confirmed')
      console.log('💰 Airdrop received:', amount, 'SOL')
      return true
    } catch (e: any) {
      error.value = 'Airdrop failed: ' + e.message
      return false
    } finally {
      loading.value = false
    }
  }

  // ============================================
  // Get Balance
  // ============================================
  const getBalance = async (): Promise<number | null> => {
    if (!publicKey.value) return null

    try {
      const balance = await connection.getBalance(publicKey.value)
      return balance / 1e9
    } catch {
      return null
    }
  }

  // ============================================
  // Error Parser
  // ============================================
  const parseError = (e: any): string => {
    const message = e.message || e.name || 'Unknown error'

    // Wallet adapter errors
    if (e.name === 'WalletNotConnectedError' || message.includes('Wallet not connected')) {
      return 'Please connect your wallet first'
    }
    if (e.name === 'WalletSignTransactionError') {
      return 'Transaction rejected by user'
    }
    
    // Solana errors
    if (message.includes('User rejected')) {
      return 'Transaction rejected by user'
    }
    if (message.includes('insufficient funds') || message.includes('Insufficient')) {
      return 'Insufficient funds. Request SOL from devnet faucet.'
    }
    if (message.includes('already in use')) {
      return 'This Product ID already exists on-chain'
    }
    if (message.includes('AccountNotFound') || message.includes('Account does not exist')) {
      return 'Product not found on-chain'
    }
    if (message.includes('0x1')) {
      return 'Insufficient funds for transaction'
    }
    if (message.includes('0x0')) {
      return 'Account already initialized'
    }

    // Program errors
    if (message.includes('ProductIdEmpty')) {
      return 'Product ID cannot be empty'
    }
    if (message.includes('ProductIdTooLong')) {
      return 'Product ID cannot exceed 32 characters'
    }
    if (message.includes('EventTypeEmpty')) {
      return 'Event type cannot be empty'
    }
    if (message.includes('EventTypeTooLong')) {
      return 'Event type cannot exceed 32 characters'
    }

    return message.length > 100 ? message.slice(0, 100) + '...' : message
  }

  // ============================================
  // Connect Phantom
  // ============================================
  const connectPhantom = async () => {
    const phantom = wallets.value.find(w => w.adapter.name === 'Phantom')
    
    if (!phantom) {
      throw new Error('Phantom wallet not found. Install it from phantom.app')
    }
    
    select(phantom.adapter.name)
    await connect()
  }

  // ============================================
  // Connection Management
  // ============================================
  async function initConnection(): Promise<boolean> {
    try {
      await connection.getLatestBlockhash()
      isReady.value = true
      connectionError.value = null
      return true
    } catch (e: any) {
      connectionError.value = e.message
      isReady.value = false
      return false
    }
  }

  async function whenReady(maxRetries = 3): Promise<void> {
    if (isReady.value) return

    for (let i = 0; i < maxRetries; i++) {
      const success = await initConnection()
      if (success) return
      
      await new Promise(r => setTimeout(r, 500 * (i + 1)))
    }

    throw new Error('Unable to connect to Solana')
  }

  // ============================================
  // Return
  // ============================================
  return {
    // Connection
    connection,
    connect,
    connecting,
    connectPhantom,
    
    // State
    loading,
    error,
    lastTxSignature,
    isReady,
    connectionError,
    
    // Wallet
    publicKey,
    isWalletConnected,
    
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
    
    // Balance & Airdrop
    getBalance,
    requestAirdrop,
    
    // Explorer URLs
    getTxExplorerUrl,
    getAddressExplorerUrl,

    // Connection management
    initConnection,
    whenReady,
  }
}