<!-- components/SolanaExplorerLink.vue -->
<template>
<v-btn
        v-bind="props"
        variant="flat"
        :size="size"
        color="primary"
        :href="explorerUrl"
        target="_blank"
      >
      
        <slot>Vedi dati on-chain</slot>
        <template #append>
          <img 
            src="/icons/solana.png" 
            :width="iconSize" 
            :height="iconSize"
            alt="Solana"
            class="ml-1"
          />
        </template>
      </v-btn>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  pdaAddress: string
  size?: 'x-small' | 'small' | 'default' | 'large'
}>()

const solscanUrl = computed(() => {
  return `https://solscan.io/account/${props.pdaAddress}?cluster=devnet`
})

const solanaFmUrl = computed(() => {
  return `https://solana.fm/address/${props.pdaAddress}?cluster=devnet-solana`
})

const explorerUrl = computed(() => {
  return `https://explorer.solana.com/address/${props.pdaAddress}?cluster=devnet`
})

const iconSize = computed(() => {
  switch (props.size) {
    case 'x-small': return 14
    case 'small': return 18
    case 'large': return 28
    default: return 22
  }
})
</script>