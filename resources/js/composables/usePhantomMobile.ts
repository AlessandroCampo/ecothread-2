import { ref } from 'vue'
import nacl from 'tweetnacl'
import bs58 from 'bs58'

const PHANTOM_CONNECT_URL = 'https://phantom.app/ul/v1/connect'
const PHANTOM_SIGN_MESSAGE_URL = 'https://phantom.app/ul/v1/signMessage'
const REDIRECT_SCHEME = 'ecothread://phantom'
const CLUSTER = 'devnet'

// Stato persistente tra le chiamate (sopravvive al redirect)
const dappKeyPair = ref<nacl.BoxKeyPair | null>(null)
const sharedSecret = ref<Uint8Array | null>(null)
const session = ref<string | null>(null)
const phantomPublicKey = ref<string | null>(null)

// Ripristina keypair da sessionStorage (persiste durante i redirect)
function getOrCreateKeyPair(): nacl.BoxKeyPair {
  const stored = sessionStorage.getItem('phantom_dapp_keypair')
  if (stored) {
    const parsed = JSON.parse(stored)
    const kp = {
      publicKey: bs58.decode(parsed.publicKey),
      secretKey: bs58.decode(parsed.secretKey),
    }
    dappKeyPair.value = kp
    return kp
  }

  const kp = nacl.box.keyPair()
  dappKeyPair.value = kp
  sessionStorage.setItem('phantom_dapp_keypair', JSON.stringify({
    publicKey: bs58.encode(kp.publicKey),
    secretKey: bs58.encode(kp.secretKey),
  }))
  return kp
}

function buildUrl(base: string, params: Record<string, string>): string {
  const url = new URL(base)
  for (const [key, val] of Object.entries(params)) {
    url.searchParams.set(key, val)
  }
  return url.toString()
}

/**
 * Apre un URL esterno forzando l'uscita dal WebView.
 * Su Android usa intent:// per aprire direttamente l'app Phantom.
 */
function openExternalUrl(httpsUrl: string) {
  const isAndroid = /Android/i.test(navigator.userAgent)

  if (isAndroid) {
    // Converte https://phantom.app/ul/v1/... in intent://
    // Questo forza Android ad aprire l'app Phantom invece di navigare nel WebView
    const urlObj = new URL(httpsUrl)
    const intentUrl = `intent://${urlObj.host}${urlObj.pathname}${urlObj.search}#Intent;scheme=https;package=app.phantom;end`
    window.location.href = intentUrl
  } else {
    // iOS: usa il link universale direttamente
    window.location.href = httpsUrl
  }
}

function decryptPayload(data: string, nonce: string, sharedSecretKey: Uint8Array): any {
  const decrypted = nacl.box.open.after(
    bs58.decode(data),
    bs58.decode(nonce),
    sharedSecretKey,
  )
  if (!decrypted) throw new Error('Impossibile decifrare la risposta di Phantom')
  return JSON.parse(new TextDecoder().decode(decrypted))
}

function encryptPayload(payload: object, sharedSecretKey: Uint8Array): { nonce: string; encryptedPayload: string } {
  const nonce = nacl.randomBytes(24)
  const encrypted = nacl.box.after(
    new TextEncoder().encode(JSON.stringify(payload)),
    nonce,
    sharedSecretKey,
  )
  return {
    nonce: bs58.encode(nonce),
    encryptedPayload: bs58.encode(encrypted),
  }
}

export function usePhantomMobile() {
  /**
   * Avvia la connessione a Phantom via deep link.
   * L'utente viene portato su Phantom, approva, e torna nell'app.
   */
  function connect() {
    const kp = getOrCreateKeyPair()

    const params = {
      dapp_encryption_public_key: bs58.encode(kp.publicKey),
      cluster: CLUSTER,
      app_url: 'https://ecothread-2-production.up.railway.app',
      redirect_link: REDIRECT_SCHEME,
    }

    const url = buildUrl(PHANTOM_CONNECT_URL, params)
    openExternalUrl(url)
  }

  /**
   * Gestisce la risposta di Phantom dopo il connect.
   * Viene chiamato quando l'app riceve il deep link di ritorno.
   */
  function handleConnectResponse(url: string): { publicKey: string } | null {
    try {
      const parsed = new URL(url)
      const params = parsed.searchParams

      const errorCode = params.get('errorCode')
      if (errorCode) {
        console.error('Phantom connect error:', params.get('errorMessage'))
        return null
      }

      const phantomEncryptionPubKey = params.get('phantom_encryption_public_key')
      const nonceStr = params.get('nonce')
      const data = params.get('data')

      if (!phantomEncryptionPubKey || !nonceStr || !data) return null

      const kp = getOrCreateKeyPair()

      // Genera shared secret con x25519
      const phantomPubKeyBytes = bs58.decode(phantomEncryptionPubKey)
      sharedSecret.value = nacl.box.before(phantomPubKeyBytes, kp.secretKey)

      // Decifra la risposta
      const decrypted = decryptPayload(data, nonceStr, sharedSecret.value)
      session.value = decrypted.session
      phantomPublicKey.value = decrypted.public_key

      // Salva in sessionStorage
      sessionStorage.setItem('phantom_session', decrypted.session)
      sessionStorage.setItem('phantom_public_key', decrypted.public_key)
      sessionStorage.setItem('phantom_shared_secret', bs58.encode(sharedSecret.value))

      return { publicKey: decrypted.public_key }
    } catch (e) {
      console.error('Error handling Phantom connect response:', e)
      return null
    }
  }

  /**
   * Avvia la firma di un messaggio via deep link Phantom.
   */
  function signMessage(message: string) {
    const storedSecret = sessionStorage.getItem('phantom_shared_secret')
    const storedSession = sessionStorage.getItem('phantom_session')

    if (!storedSecret || !storedSession) {
      throw new Error('Non connesso a Phantom. Connettiti prima.')
    }

    const kp = getOrCreateKeyPair()
    const secret = bs58.decode(storedSecret)

    const payload = {
      message: bs58.encode(new TextEncoder().encode(message)),
      session: storedSession,
    }

    const { nonce, encryptedPayload } = encryptPayload(payload, secret)

    const params = {
      dapp_encryption_public_key: bs58.encode(kp.publicKey),
      nonce,
      redirect_link: REDIRECT_SCHEME,
      payload: encryptedPayload,
    }

    const url = buildUrl(PHANTOM_SIGN_MESSAGE_URL, params)
    openExternalUrl(url)
  }

  /**
   * Gestisce la risposta di Phantom dopo la firma.
   */
  function handleSignResponse(url: string): { signature: Uint8Array } | null {
    try {
      const parsed = new URL(url)
      const params = parsed.searchParams

      const errorCode = params.get('errorCode')
      if (errorCode) {
        console.error('Phantom sign error:', params.get('errorMessage'))
        return null
      }

      const nonceStr = params.get('nonce')
      const data = params.get('data')

      if (!nonceStr || !data) return null

      const storedSecret = sessionStorage.getItem('phantom_shared_secret')
      if (!storedSecret) return null

      const secret = bs58.decode(storedSecret)
      const decrypted = decryptPayload(data, nonceStr, secret)

      return { signature: bs58.decode(decrypted.signature) }
    } catch (e) {
      console.error('Error handling Phantom sign response:', e)
      return null
    }
  }

  /**
   * Controlla se siamo in un'app Capacitor.
   */
  function isCapacitor(): boolean {
    return !!(window as any).Capacitor
  }

  /**
   * Restituisce la public key salvata (dopo connect).
   */
  function getStoredPublicKey(): string | null {
    return sessionStorage.getItem('phantom_public_key')
  }

  /**
   * Pulisce lo stato della sessione Phantom.
   */
  function disconnect() {
    sessionStorage.removeItem('phantom_dapp_keypair')
    sessionStorage.removeItem('phantom_session')
    sessionStorage.removeItem('phantom_public_key')
    sessionStorage.removeItem('phantom_shared_secret')
    sharedSecret.value = null
    session.value = null
    phantomPublicKey.value = null
    dappKeyPair.value = null
  }

  return {
    connect,
    handleConnectResponse,
    signMessage,
    handleSignResponse,
    isCapacitor,
    getStoredPublicKey,
    disconnect,
    phantomPublicKey,
  }
}
