<template>
      <v-app>

     <v-snackbar
    v-model="visible"
    :color="snack?.type"
    :timeout="snack?.timeout ?? 4000"
    position="right"
    :close-on-content-click="true"
  >
    {{ snack?.message }}
    
    <template #actions>
      <v-btn
        v-if="snack?.action"
        variant="text"
        :href="snack.action.href"
        :target="snack.action.href ? '_blank' : undefined"
        @click="snack.action.onClick?.(); hide()"
      >
        {{ snack.action.text }}
        <v-icon v-if="snack.action.href" end size="small">mdi-open-in-new</v-icon>
      </v-btn>
      <v-btn v-else variant="text" @click="hide">Chiudi</v-btn>
    </template>
  </v-snackbar>
    <v-main class="layout bg-background">
      <app-header/>
      <v-container id="main-container">
          <slot />
    </v-container>
</v-main>
</v-app>
</template>

<script setup>
import AppHeader from "@/components/AppHeader.vue";
import { useSnack } from '@/composables/useSnack'

const { snack, visible, hide } = useSnack()


</script>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    overflow: hidden;
}


.layout {
    height: 100vh;
    width: 100vw;
    overflow-y: auto;
}

#main-container {
  max-height: 100%;
  width: 100%;
}



</style>