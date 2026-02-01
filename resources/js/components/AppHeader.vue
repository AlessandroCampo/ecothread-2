<template>
   <v-app-bar color="primary-lighten-4" v-if="page.props.user" height="70">
            <v-app-bar-title>
              <v-img width="250" src="/logo.png" class="d-none d-md-block"/>
              <v-img width="50" src="/logo-mobile.png" class="d-block d-md-none"
               
              />
            </v-app-bar-title>
            <v-spacer />
 
      <v-menu min-width="200px">
        <template v-slot:activator="{ props }">
          <v-btn
            icon
            v-bind="props"
          >
           <company-avatar :user :wallet-short/>
          </v-btn>
        </template>
        <v-card>
          <v-card-text>
            <div class="mx-auto text-center">
                <company-avatar :user :wallet-short/>
              <h3 class="mt-2">{{ user.name ||  walletShort }}</h3>
              <p class="text-caption mt-1" v-if="user.email">
                {{ user.email}}
              </p>
              <v-divider class="my-3"></v-divider>
              <v-btn
                variant="text"
                rounded
                color="primary"
                append-icon="mdi-account-edit-outline"
              >
                Edit Account
                <profile-edit-dialog/>
              </v-btn>
              <v-divider class="my-3"></v-divider>
              <v-btn
                variant="text"
                rounded
                color="error"
                append-icon="mdi-logout"
              >
                Disconnect
              </v-btn>
            </div>
          </v-card-text>
        </v-card>
      </v-menu>
 
           
        </v-app-bar>
</template>

<script setup>

import { usePage, Link, router } from "@inertiajs/vue3";
import {ref, computed} from 'vue';
import ProfileEditDialog from "./ProfileEditDialog.vue";
import CompanyAvatar from "./CompanyAvatar.vue";


/*  
  <v-chip variant="outlined" class="mr-2">{{ walletShort }}</v-chip>
            <v-btn variant="text" @click="logout">Logout</v-btn>
*/
const page = usePage();

const user = computed(() => page.props.user);

const walletShort = computed(() => {
  const w = user.value?.wallet_address
  return w ? w.slice(0, 4) + '...' + w.slice(-4) : ''
})

const logout = async () => {
  await api.post('/auth/logout')
  router.visit('/login')
}
</script>