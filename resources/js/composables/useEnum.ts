import { onMounted, ref } from 'vue'
import api from '@/lib/axios'

interface EnumOption {
  value: string
  label: string
  icon?: string
  color?: string
  description?: string
}

export interface ProductType {
  value: string
  label: string
  icon: string
  category: string
}

interface EventTypeOption extends EnumOption {
  is_required: boolean
  sort_order: number
}

// State condiviso (fuori dalla funzione per persistere tra componenti)
const eventTypes = ref<EventTypeOption[]>([])
const trustLevels = ref<EnumOption[]>([])
const productTypes = ref<ProductType[]>([])

const eventEnums = ref<{
  materials: EnumOption[]
  processes: EnumOption[]
  transportModes: EnumOption[]
  certificationTypes: EnumOption[]
  packagingMaterials: EnumOption[]
  countries: EnumOption[]
}>({
  materials: [],
  processes: [],
  transportModes: [],
  certificationTypes: [],
  packagingMaterials: [],
  countries: [],
})

const loaded = ref(false)
const eventEnumsLoaded = ref(false)
const loading = ref(false)
const error = ref<string | null>(null)

export function useEnums() {
  const loadEnums = async () => {
    if (loaded.value || loading.value) return

    loading.value = true
    error.value = null

    try {
      const { data } = await api.get('/api/enums')
      eventTypes.value = data.event_types
      trustLevels.value = data.trust_levels
      productTypes.value = data.product_types
      loaded.value = true
    } catch (e: any) {
      error.value = e.message
      console.error('Failed to load enums:', e)
    } finally {
      loading.value = false
    }
  }

  const loadEventEnums = async () => {
    if (eventEnumsLoaded.value) return

    try {
      const { data } = await api.get('/api/enums/events')
      eventEnums.value = {
        materials: data.materials,
        processes: data.processes,
        transportModes: data.transport_modes,
        certificationTypes: data.certification_types,
        packagingMaterials: data.packaging_materials,
        countries: data.countries,
      }
      eventEnumsLoaded.value = true
    } catch (e: any) {
      console.error('Failed to load event enums:', e)
    }
  }

  // Auto-load enum comuni al mounted
  onMounted(() => {
    if (!loaded.value) {
      loadEnums()
    }
  })

  return {
    eventTypes,
    trustLevels,
    productTypes,
    eventEnums,
    loaded,
    eventEnumsLoaded,
    loading,
    error,
    loadEnums,
    loadEventEnums,
  }
}