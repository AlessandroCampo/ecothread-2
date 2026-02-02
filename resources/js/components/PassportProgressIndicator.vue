<template>
  <div class="d-flex align-center">
    <v-progress-linear
      :model-value="percentage"
      :color="progressColor"
      height="6"
      rounded
      class="flex-grow-1"
      style="max-width: 60px"
    />
    <span :class="['ml-1 text-caption', textColorClass]">
      {{ progress?.count || 0 }}/{{ progress?.total || 4 }}
    </span>

    <v-tooltip location="top">
      <template #activator="{ props: tooltipProps }">
        <v-icon
          v-bind="tooltipProps"
          size="x-small"
          class="ml-1"
          color="grey"
        >
          mdi-information-outline
        </v-icon>
      </template>
      <div class="text-caption">
        <div v-if="progress?.missing?.length">
          <strong>Mancanti:</strong>
          <ul class="pl-3 mb-0">
            <li v-for="type in progress.missing" :key="type">
              {{ eventTypeLabels[type] }}
            </li>
          </ul>
        </div>
        <div v-else class="text-success">
          Tutti i requisiti soddisfatti!
        </div>
      </div>
    </v-tooltip>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  progress: {
    type: Object,
    default: () => ({})
  }
})

const eventTypeLabels = {
  ORIGIN: 'Origine',
  PRODUCTION: 'Produzione',
  TRANSPORT: 'Trasporto',
  ENV_CLAIM: 'Dich. Ambientale'
}

const percentage = computed(() => {
  if (!props.progress) return 0
  return (props.progress.count / props.progress.total) * 100
})

const progressColor = computed(() => {
  const p = percentage.value
  if (p === 100) return 'success'
  if (p >= 50) return 'warning'
  return 'error'
})

const textColorClass = computed(() => {
  const p = percentage.value
  if (p === 100) return 'text-success'
  if (p >= 50) return 'text-warning'
  return 'text-error'
})
</script>
