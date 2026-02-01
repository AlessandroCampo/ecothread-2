<template>
  <div v-if="eventType" class="event-metadata-form">
    
    <!-- ORIGIN -->
    <template v-if="eventType === 'ORIGIN'">
      <country-list
        v-model="metadata.country"
        label="Paese di provenienza*"
        variant="outlined"
        class="mb-3"
        :disabled="disabled"
        :error-messages="getError('country')"
      />

      <div class="text-body-2 font-weight-bold d-flex justify-space-between align-center mb-3">
        Fibre utilizzate
        <v-btn
          variant="tonal"
          size="x-small"
          prepend-icon="mdi-plus"
          :disabled="disabled || totalPercentage >= 100"
          @click="addComposition"
          color="primary-darken-1"
        >
          Aggiungi fibra
        </v-btn>
      </div>

      <v-alert 
        v-if="totalPercentage !== 100 && metadata.compositions?.length > 0" 
        type="warning" 
        variant="tonal" 
        density="compact" 
        class="my-3 text-body-2"
      >
        La composizione totale è {{ totalPercentage }}% (deve essere 100%)
      </v-alert>

      <!-- Errore generale sulle composizioni -->
      <v-alert 
        v-if="getError('compositions')" 
        type="error" 
        variant="tonal" 
        density="compact" 
        class="my-3 text-body-2"
      >
        {{ getError('compositions') }}
      </v-alert>

      <v-sheet 
        v-for="(comp, index) in metadata.compositions" 
        :key="index"
        variant="outlined" 
        class="mb-3 pa-3 border-grey-lighten-1 border-sm border-opacity-100 rounded-lg"
        :class="{ 'border-error': hasCompositionError(index) }"
      >
        <v-row dense align="center">
          <v-col cols="6">
            <v-autocomplete
              v-model="comp.material"
              :items="eventEnums.materials"
              item-title="label"
              item-value="value"
              label="Tipo di fibra*"
              variant="outlined"
              density="compact"
              :hide-details="!getError(`compositions.${index}.material`)"
              :error-messages="getError(`compositions.${index}.material`)"
              :disabled="disabled"
            />
          </v-col>
          <v-col cols="5">
            <v-text-field
              v-model.number="comp.percentage"
              label="Percentuale*"
              type="number"
              min="0"
              max="100"
              suffix="%"
              variant="outlined"
              density="compact"
              :hide-details="!getError(`compositions.${index}.percentage`)"
              :error-messages="getError(`compositions.${index}.percentage`)"
              :disabled="disabled"
            />
          </v-col>
          <v-col cols="1" class="d-flex justify-center">
            <v-btn
              v-if="metadata.compositions.length > 1"
              icon="mdi-delete"
              size="small"
              variant="text"
              color="error"
              :disabled="disabled"
              @click="removeComposition(index)"
            />
          </v-col>
        </v-row>
      </v-sheet>
    </template>

    <!-- PRODUCTION -->
    <template v-else-if="eventType === 'PRODUCTION'">
      <v-autocomplete
        v-model="metadata.processes"
        :items="eventEnums.processes"
        item-title="label"
        item-value="value"
        label="Lavorazioni effettuate*"
        variant="outlined"
        class="mb-3"
        multiple
        chips
        closable-chips
        :disabled="disabled"
        :error-messages="getError('processes')"
      />

      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model.number="metadata.water_usage_liters"
            label="Consumo idrico"
            type="number"
            min="0"
            suffix="litri/capo"
            variant="outlined"
            :disabled="disabled"
            :error-messages="getError('water_usage_liters')"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model.number="metadata.energy_kwh"
            label="Consumo energetico"
            type="number"
            min="0"
            step="0.1"
            suffix="kWh/capo"
            variant="outlined"
            :disabled="disabled"
            :error-messages="getError('energy_kwh')"
          />
        </v-col>
      </v-row>
    </template>

    <!-- TRANSPORT -->
    <template v-else-if="eventType === 'TRANSPORT'">
      <v-row>
        <v-col cols="12" sm="6">
          <country-list
            v-model="metadata.origin_country"
            label="Paese di partenza*"
            :disabled="disabled"
            :error-messages="getError('origin_country')"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <country-list
            v-model="metadata.destination_country"
            label="Paese di destinazione*"
            :disabled="disabled"
            :error-messages="getError('destination_country')"
          />
        </v-col>
      </v-row>

      <v-select
        v-model="metadata.transport_mode"
        :items="eventEnums.transportModes"
        item-title="label"
        item-value="value"
        label="Modalità di trasporto*"
        variant="outlined"
        class="mb-3"
        :disabled="disabled"
        :error-messages="getError('transport_mode')"
      >
        <template #selection="{ item }">
          <v-icon :icon="item.raw.icon" class="me-2"/>
          {{ item.title }}
        </template>
        <template #item="{ props, item }">
          <v-list-item v-bind="props">
            <template #prepend>
              <v-icon :icon="item.raw.icon"/>
            </template>
          </v-list-item>
        </template>
      </v-select>

      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model.number="metadata.distance_km"
            label="Distanza"
            type="number"
            min="0"
            suffix="km"
            variant="outlined"
            :disabled="disabled"
            :error-messages="getError('distance_km')"
          />
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model.number="metadata.co2_kg"
            label="Emissioni CO₂"
            type="number"
            min="0"
            step="0.1"
            suffix="kg"
            variant="outlined"
            :disabled="disabled"
            :error-messages="getError('co2_kg')"
          />
        </v-col>
      </v-row>
    </template>

    <!-- PACKAGING -->
    <template v-else-if="eventType === 'PACKAGING'">
      <v-autocomplete
        v-model="metadata.materials"
        :items="eventEnums.packagingMaterials"
        item-title="label"
        item-value="value"
        label="Materiali utilizzati*"
        variant="outlined"
        class="mb-3"
        multiple
        chips
        closable-chips
        :disabled="disabled"
        :error-messages="getError('materials')"
      />

      <v-checkbox
        v-model="metadata.is_recyclable"
        label="Packaging riciclabile"
        :disabled="disabled"
        class="text-grey-darken-1"
        :error-messages="getError('is_recyclable')"
      />
    </template>

    <!-- RECYCLE -->
    <template v-else-if="eventType === 'RECYCLE'">
      <v-slider
        v-model="metadata.recycle_percentage"
        label="Percentuale riciclabilità"
        :min="0"
        :max="100"
        :step="5"
        thumb-label="always"
        class="mb-3"
        :disabled="disabled"
        :error-messages="getError('recycle_percentage')"
      >
        <template #append>
          <span class="text-body-2">{{ metadata.recycle_percentage }}%</span>
        </template>
      </v-slider>

      <v-checkbox
        v-model="metadata.take_back_program"
        label="Programma di ritiro offerto dal brand"
        :disabled="disabled"
        class="text-grey-darken-1"
        :error-messages="getError('take_back_program')"
      />
    </template>

    <!-- CERTIFICATION -->
    <template v-else-if="eventType === 'CERTIFICATION'">
      <v-autocomplete
        v-model="metadata.certification_type"
        :items="eventEnums.certificationTypes"
        item-title="label"
        item-value="value"
        label="Tipo di certificazione*"
        variant="outlined"
        class="mb-3"
        :disabled="disabled"
        :error-messages="getError('certification_type')"
      />

      <v-text-field
        v-model="metadata.issued_by"
        label="Ente certificatore*"
        variant="outlined"
        class="mb-3"
        :disabled="disabled"
        :error-messages="getError('issued_by')"
      />

      <v-row>
        <v-col cols="12" sm="6">
          <v-text-field
            label="Data di scadenza"
            variant="outlined"
            :disabled="disabled"
            readonly
            :model-value="metadata.valid_until ? date.format(metadata.valid_until, 'keyboardDate') : ''"
            :error-messages="getError('valid_until')"
          >
            <v-menu activator="parent">
              <v-date-picker elevation="24" v-model="metadata.valid_until" />
            </v-menu>
          </v-text-field>
        </v-col>
        <v-col cols="12" sm="6">
          <v-text-field
            v-model="metadata.certificate_number"
            label="Numero certificato"
            variant="outlined"
            :disabled="disabled"
            :error-messages="getError('certificate_number')"
          />
        </v-col>
      </v-row>
    </template>

    <!-- ENV_CLAIM / CUSTOM: nessun campo aggiuntivo -->
    <template v-else-if="eventType === 'ENV_CLAIM' || eventType === 'CUSTOM'">
      <v-alert type="info" variant="tonal" density="compact" class="mb-3 text-body-2">
        Nessun dato aggiuntivo richiesto. Usa il campo descrizione per i dettagli.
      </v-alert>
    </template>

  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useEnums } from '@/composables/useEnum'
import { useDate } from 'vuetify'
import 'flag-icons/css/flag-icons.min.css'
import CountryList from './CountryList.vue'

const date = useDate()

const props = defineProps<{
  eventType: string | null
  metadata: Record<string, any>
  disabled?: boolean
  errors?: Record<string, string[]>
}>()

const { eventEnums, loadEventEnums } = useEnums()

onMounted(() => {
  loadEventEnums()
})

// Computed per validazione composizione
const totalPercentage = computed(() => {
  if (!props.metadata.compositions) return 0
  return props.metadata.compositions.reduce(
    (sum: number, c: any) => sum + (c.percentage || 0), 
    0
  )
})

// Helper per ottenere errore da chiave
function getError(key: string): string | undefined {
  if (!props.errors) return undefined
  const error = props.errors[key]
  return error ? error[0] : undefined
}

// Helper per verificare se una composizione ha errori
function hasCompositionError(index: number): boolean {
  if (!props.errors) return false
  return Object.keys(props.errors).some(key => key.startsWith(`compositions.${index}.`))
}

// Methods per gestione composizioni
function addComposition() {
  if (!props.metadata.compositions) {
    props.metadata.compositions = []
  }
  props.metadata.compositions.push({
    material: null,
    percentage: 0,
  })
}

function removeComposition(index: number) {
  props.metadata.compositions.splice(index, 1)
}
</script>

<style scoped>
.border-error {
  border-color: rgb(var(--v-theme-error)) !important;
}
</style>