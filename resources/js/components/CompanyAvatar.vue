<template>
 <v-avatar
              color="secondary-lighten-1"
              border="sm"
            >
            <v-img  :src="user.logo_url" v-if="user.logo_url"/>
            <span v-else class="text-h6 text-uppercase">
              {{ user.name ? user.name[0] : walletShort[0] }}
            </span>
            </v-avatar>
</template>

<script setup>

const props = defineProps({
    user: {type: Object},
    walletShort: {type: String}
})

</script>