# EcoThread - Integrazione Wallet Passkey + Solana

## Architettura

```
┌─────────────────────────────────────────────────────────────────────┐
│                         FRONTEND (Vue.js)                           │
├─────────────────────────────────────────────────────────────────────┤
│  usePasskeyAuth                    useSolanaPasskey                 │
│  ├── login/register (WebAuthn)     ├── createProduct()             │
│  ├── unlockWallet() → passkey      ├── addEvent()                  │
│  ├── signTransaction()             └── costruisce tx + firma       │
│  └── chiave privata in memoria                                      │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                    firma base64 + tx serializzata
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                    SOLANA SERVICE (Node.js:3001)                    │
├─────────────────────────────────────────────────────────────────────┤
│  POST /sign-and-submit                                              │
│  ├── Riceve: transaction (base64), signerSignature, signerPublicKey │
│  ├── Verifica firma utente                                          │
│  ├── Firma con fee payer EcoThread                                  │
│  └── Invia a Solana → restituisce txSignature                      │
└────────────────────────────────┬────────────────────────────────────┘
                                 │
                                 ▼
┌─────────────────────────────────────────────────────────────────────┐
│                         SOLANA DEVNET                               │
│  ├── creator = wallet azienda (firmato con passkey)                 │
│  └── fee payer = wallet EcoThread (firmato server-side)             │
└─────────────────────────────────────────────────────────────────────┘
```

## Setup

### 1. Servizio Solana (Node.js)

```bash
cd solana-builder

# Installa dipendenze
npm install

# Configura .env
cp .env.example .env

# Genera un wallet per le fee (se non ne hai uno)
solana-keygen new --no-bip39-passphrase -o fee-payer.json

# Ottieni la chiave privata in base58
node -e "const bs58=require('bs58');const k=require('./fee-payer.json');console.log(bs58.encode(Buffer.from(k)))"

# Copia l'output in .env come SOLANA_FEE_PAYER_PRIVATE_KEY

# Richiedi SOL per le fee (devnet)
solana airdrop 2 $(solana-keygen pubkey fee-payer.json) --url devnet

# Avvia il servizio
npm run dev
```

### 2. Laravel Backend

```bash
# Configura .env con le variabili Solana
cp .env.example .env
php artisan key:generate

# Migra database
php artisan migrate

# Avvia server
php artisan serve
```

### 3. Frontend

```bash
# Aggiungi VITE_SOLANA_SERVICE_URL al .env
echo "VITE_SOLANA_SERVICE_URL=http://localhost:3001" >> .env

# Build/dev
npm run dev
```

## Uso nei Componenti

### Sostituire useSolana con useSolanaPasskey

```typescript
// PRIMA (con Phantom)
import { useSolana } from '@/composables/useSolana'
const { createProduct, isWalletConnected } = useSolana()

// DOPO (con Passkey)
import { useSolanaPasskey } from '@/composables/useSolanaPasskey'
const { createProduct, isWalletConnected, unlockWallet } = useSolanaPasskey()
```

### Esempio: ProductForm.vue

```vue
<script setup lang="ts">
import { useSolanaPasskey } from '@/composables/useSolanaPasskey'

const { 
  createProduct: createProductOnChain, 
  isWalletConnected,
  isWalletUnlocked,
  unlockWallet,
  getAddressExplorerUrl,
} from useSolanaPasskey()

async function submitCreate() {
  // La passkey viene richiesta automaticamente se il wallet è bloccato
  const result = await createProductOnChain(form.value.id)
  
  if (result.success) {
    console.log('Transazione:', result.txSignature)
    console.log('PDA:', result.pdaAddress)
  } else {
    console.error('Errore:', result.error)
  }
}
</script>
```

## Flusso Completo

1. **Utente si registra** → genera wallet + passkey
2. **Utente vuole creare prodotto** → clicca "Crea"
3. **Frontend** → costruisce transazione Solana con Anchor
4. **Sistema richiede passkey** → utente autentica con biometria/PIN
5. **Frontend firma** → con chiave privata decriptata
6. **Frontend invia** → tx + firma al servizio Node.js
7. **Servizio verifica firma** → valida che l'utente abbia firmato
8. **Servizio firma come fee payer** → aggiunge firma EcoThread
9. **Servizio invia a Solana** → broadcast transazione
10. **Utente vede conferma** → con link a Solana Explorer

## Differenze da Phantom

| Aspetto | Phantom | Passkey Wallet |
|---------|---------|----------------|
| Installazione | Richiede estensione | Nessuna |
| UX Mobile | Deep link problematici | Nativo |
| Chi paga fee | L'utente | EcoThread |
| Firma | Popup Phantom | Biometria/PIN |
| Recupero | Seed phrase utente | Seed phrase + passkey |
| On-chain creator | Wallet utente | Wallet utente ✓ |

## Sicurezza

- La chiave privata è criptata AES-256-GCM
- La passkey sblocca la decryption
- La chiave privata rimane solo in memoria
- Il server non vede MAI la chiave privata
- Il fee payer può solo pagare, non può firmare per l'utente

## Troubleshooting

### "Fee payer non disponibile"
- Verifica che il servizio Node.js sia in esecuzione
- Controlla `VITE_SOLANA_SERVICE_URL` nel .env frontend

### "Firma non valida"
- La chiave pubblica non corrisponde al wallet dell'utente
- L'utente potrebbe aver cambiato dispositivo/passkey

### "Fondi insufficienti nel fee payer"
- Richiedi airdrop: `solana airdrop 2 <FEE_PAYER_ADDRESS> --url devnet`

### Transazione fallisce
- Controlla i log del servizio Node.js
- Verifica che il program ID sia corretto
- Assicurati che il prodotto non esista già on-chain
