<template>
  <v-list-item
    :class="{ 'bg-primary-lighten-5': isSelected }"
    @click="$emit('select', product)"
  >
    <template #prepend>
      <v-badge
        v-if="passportBadgeIcon"
        :color="passportBadgeColor"
        :icon="passportBadgeIcon"
        location="bottom end"
        offset-x="3"
        offset-y="3"
      >
        <v-icon :color="product.is_on_chain ? 'green' : 'orange'" size="28">
          {{ product.is_on_chain ? 'mdi-check-circle' : 'mdi-clock' }}
        </v-icon>
      </v-badge>
      <v-icon v-else :color="product.is_on_chain ? 'green' : 'orange'" size="28">
        {{ product.is_on_chain ? 'mdi-check-circle' : 'mdi-clock' }}
      </v-icon>
    </template>

    <v-list-item-title class="d-flex align-center">
      {{ product.name }}
      <v-chip
        v-if="product.passport_progress?.has_passport"
        size="x-small"
        color="success"
        variant="tonal"
        class="ml-2"
      >
        <v-icon size="x-small">mdi-passport</v-icon>
      </v-chip>
    </v-list-item-title>

    <v-list-item-subtitle class="d-flex align-center ga-2">
      <span class="text-truncate" style="max-width: 120px">{{ product.id }}</span>
      <template v-if="!product.passport_progress?.has_passport">
        <PassportProgressIndicator :progress="product.passport_progress" />
      </template>
      <span v-else class="text-success text-caption d-none d-md-block">
        <v-icon size="x-small">mdi-check</v-icon> Certificato
      </span>
    </v-list-item-subtitle>

    <template #append>
      <div class="d-flex align-center">
        <v-img
          v-if="product.image_url"
          :src="product.image_url"
          width="40"
          height="40"
          class="rounded mr-2"
          cover
        />

        <v-menu>
          <template #activator="{ props: menuProps }">
            <v-btn
              icon="mdi-dots-vertical"
              variant="text"
              size="small"
              v-bind="menuProps"
              @click.stop
            />
          </template>

          <v-list density="compact">
            <v-list-item
              prepend-icon="mdi-pencil"
              title="Modifica"
              @click.stop="$emit('edit', product)"
            />
            <v-list-item
              prepend-icon="mdi-timeline-plus"
              title="Aggiungi evento"
              :disabled="!product.is_on_chain"
              @click.stop="$emit('add-event', product)"
            />
            <v-divider class="my-1" />
            <v-list-item
              v-if="product.passport_progress?.eligible && !product.passport_progress?.has_passport"
              prepend-icon="mdi-passport"
              title="Richiedi passaporto"
              @click.stop="$emit('request-passport', product)"
            />
            <v-list-item
              v-else-if="!product.passport_progress?.has_passport"
              prepend-icon="mdi-clipboard-check-outline"
              title="Vedi requisiti"
              @click.stop="$emit('show-requirements', product)"
            />
            <v-list-item
              v-if="product.passport_progress?.has_passport"
              prepend-icon="mdi-passport"
              title="Vedi passaporto"
              @click.stop="$emit('view-passport', product)"
            />
          </v-list>
        </v-menu>
      </div>
    </template>
  </v-list-item>
</template>

<script setup>
import { computed } from 'vue'
import PassportProgressIndicator from './PassportProgressIndicator.vue'

const props = defineProps({
  product: {
    type: Object,
    required: true
  },
  isSelected: {
    type: Boolean,
    default: false
  }
})

defineEmits(['select', 'edit', 'add-event', 'request-passport', 'show-requirements', 'view-passport'])

const passportBadgeColor = computed(() => {
  const progress = props.product.passport_progress
  if (progress?.has_passport) return 'success'
  if (progress?.eligible) return 'info'
  return null
})

const passportBadgeIcon = computed(() => {
  const progress = props.product.passport_progress
  if (progress?.has_passport) return 'mdi-passport'
  if (progress?.eligible) return 'mdi-check'
  return null
})
</script>
