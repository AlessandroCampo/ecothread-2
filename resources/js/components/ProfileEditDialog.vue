<template>

<!-- Profile Edit Dialog -->
<v-dialog  max-width="500" activator="parent" :persistent="true">
    <template #default="{isActive}">
        <v-card>
          <v-card-title class="d-flex align-center py-4 px-6">
            <v-icon class="mr-2">mdi-account-edit-outline</v-icon>
            Profilo Azienda
          </v-card-title>
          
          <v-divider />
          
          <v-card-text class="pa-6">
            <!-- Logo Upload -->
            <div class="text-center mb-6">
              <v-avatar size="100" color="primary-lighten-4" class="mb-3">
                <v-img v-if="form.logo_url" :src="form.logo_url" />
                <v-icon v-else size="48" color="primary">mdi-domain</v-icon>
              </v-avatar>
              <div>
                <v-btn
                  variant="tonal"
                  color="primary"
                  size="small"
                  prepend-icon="mdi-upload"
                  @click="triggerLogoUpload"
                >
                  Carica Logo
                </v-btn>
                <input
                  ref="logoInput"
                  type="file"
                  accept="image/*"
                  class="d-none"
                  @change="handleLogoChange"
                />
              </div>
            </div>
      
            <!-- Form Fields -->
            <v-text-field
              v-model="form.name"
              label="Nome Azienda"
              prepend-inner-icon="mdi-office-building"
              variant="outlined"
              density="comfortable"
              class="mb-4"
            />
      
            <v-text-field
              v-model="form.email"
              label="Email di contatto"
              type="email"
              prepend-inner-icon="mdi-email-outline"
              variant="outlined"
              density="comfortable"
              class="mb-4"
            
              persistent-hint
            />
      
            <v-text-field
              v-model="form.website"
              label="Sito Web"
              prepend-inner-icon="mdi-web"
              variant="outlined"
              density="comfortable"
              placeholder="https://"
            
              persistent-hint
            />
          </v-card-text>
      
          <v-divider />
      
          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn
              variant="text"
              @click="isActive.value = false"
              color="error"
            >
              Annulla
            </v-btn>
            <v-btn
              color="primary"
              variant="flat"
              :loading="form.processing"
              @click="saveProfile(isActive)"
            >
              Salva
            </v-btn>
          </v-card-actions>
        </v-card>
    </template>
</v-dialog>

</template>


<script setup>

import { useForm, usePage } from '@inertiajs/vue3'
import {ref} from 'vue';

const page = usePage();

const logoInput = ref(null)

const form = useForm({
  name: page.props.user?.name ?? '',
  email: page.props.user?.email ?? '',
  website: page.props.user?.website ?? '',
  logo_url: page.props.user?.logo_url ?? '',
  logo: null
})

const triggerLogoUpload = () => logoInput.value?.click()

const handleLogoChange = (e) => {
  const file = e.target.files[0]
  if (file) {
    form.logo = file
    form.logo_url = URL.createObjectURL(file)
  }
}

const saveProfile = (isEditDialogActive) => {
  form.post(route('profile.update'), {
    onSuccess: () => isEditDialogActive.value = false
  })
}

</script>