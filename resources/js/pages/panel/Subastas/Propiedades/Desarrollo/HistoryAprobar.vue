<template>
  <Toast />

  <Dialog 
    v-model:visible="visible" 
    header="Historial de Aprobaciones" 
    :modal="true" 
    :style="{ width: '700px' }"
    @hide="handleClose"
  >
    <div v-if="loading" class="flex justify-center items-center p-6">
      <i class="pi pi-spin pi-spinner text-4xl text-blue-500"></i>
      <p class="mt-2 text-gray-600">Cargando historial...</p>
    </div>

    <div v-else-if="historial.length === 0" class="text-center p-6">
      <i class="pi pi-inbox text-6xl text-gray-300 mb-3"></i>
      <p class="text-gray-500 text-lg">No hay registros en el historial</p>
      <p class="text-gray-400 text-sm mt-2">Aún no se han realizado acciones sobre esta solicitud</p>
    </div>

    <div v-else class="flex flex-col gap-4">
      <!-- Timeline -->
      <div class="relative">
        <div 
          v-for="(item, index) in historial" 
          :key="item.id"
          class="flex gap-4 pb-6 relative"
        >
          <!-- Línea vertical -->
          <div 
            v-if="index !== historial.length - 1"
            class="absolute left-5 top-10 w-0.5 h-full bg-gray-300"
            style="z-index: 0;"
          ></div>

          <!-- Icono de estado -->
          <div 
            class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center relative z-10"
            :class="getStatusColor(item.status)"
          >
            <i :class="getStatusIcon(item.status)" class="text-white text-lg"></i>
          </div>

          <!-- Contenido -->
          <div class="flex-grow">
            <div class="bg-white border-1 border-300 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow">
              <!-- Header -->
              <div class="flex justify-between items-start mb-3">
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <Tag 
                      :value="getStatusLabel(item.status)" 
                      :severity="getStatusSeverity(item.status)"
                      class="text-sm"
                    />
                    <span class="text-xs text-gray-500">ID: {{ item.id }}</span>
                  </div>
                  <p class="text-sm text-gray-700 font-semibold">
                    <i class="pi pi-user mr-1"></i>
                    {{ item.approved_by }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-gray-500 mb-1">
                    <i class="pi pi-calendar mr-1"></i>
                    {{ formatDate(item.approved_at) }}
                  </p>
                  <p class="text-xs text-gray-400">
                    <i class="pi pi-clock mr-1"></i>
                    {{ formatTime(item.approved_at) }}
                  </p>
                </div>
              </div>

              <!-- Comentario -->
              <div v-if="item.comment" class="mt-3 pt-3 border-t-1 border-gray-200">
                <label class="text-xs font-semibold text-gray-600 mb-1 block">
                  <i class="pi pi-comment mr-1"></i>
                  Comentario:
                </label>
                <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-md whitespace-pre-wrap">
                  {{ item.comment }}
                </p>
              </div>

              <div v-else class="mt-3 pt-3 border-t-1 border-gray-200">
                <p class="text-xs text-gray-400 italic">
                  <i class="pi pi-info-circle mr-1"></i>
                  Sin comentarios
                </p>
              </div>

              <!-- Badge de tiempo relativo -->
              <div class="mt-3 flex justify-end">
                <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
                  {{ getRelativeTime(item.approved_at) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Resumen -->
      <div class="mt-4 p-4 bg-blue-50 border-1 border-blue-200 rounded-lg">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <i class="pi pi-info-circle text-blue-600"></i>
            <span class="font-semibold text-blue-900">Total de registros:</span>
            <span class="text-blue-700">{{ historial.length }}</span>
          </div>
          <div class="flex gap-3 text-sm">
            <span class="text-green-700">
              <i class="pi pi-check-circle mr-1"></i>
              Aprobadas: {{ contarPorEstado('approved') }}
            </span>
            <span class="text-red-700">
              <i class="pi pi-times-circle mr-1"></i>
              Rechazadas: {{ contarPorEstado('rejected') }}
            </span>
            <span class="text-orange-700">
              <i class="pi pi-exclamation-circle mr-1"></i>
              Observadas: {{ contarPorEstado('observed') }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-between items-center w-full">
        <Button 
          label="Exportar" 
          icon="pi pi-download" 
          severity="secondary" 
          text
          size="small"
          @click="exportarHistorial"
          :disabled="historial.length === 0"
        />
        <Button 
          label="Cerrar" 
          icon="pi pi-times" 
          severity="secondary"
          @click="handleClose" 
        />
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import axios from 'axios'
import { useToast } from 'primevue/usetoast'
import Dialog from 'primevue/dialog'
import Toast from 'primevue/toast'
import Button from 'primevue/button'
import Tag from 'primevue/tag'

const props = defineProps({
  visible: { type: Boolean, default: false },
  idPropiedad: { type: [String, Number], default: null }
})

const emit = defineEmits(['update:visible'])

const toast = useToast()
const loading = ref(false)
const visible = ref(props.visible)
const historial = ref([])

// Watchers
watch(() => props.visible, async (newVal) => {
  visible.value = newVal
  if (newVal && props.idPropiedad) {
    await cargarHistorial()
  }
})

watch(visible, (newVal) => {
  emit('update:visible', newVal)
  if (!newVal) {
    resetForm()
  }
})

const cargarHistorial = async () => {
  if (!props.idPropiedad) return
  
  loading.value = true
  try {
    const { data } = await axios.get(`/solicitud-activacion/${props.idPropiedad}/historial`)
    
    // Los datos vienen en data.data
    historial.value = data.data || []
    
  } catch (error) {
    console.error('Error al cargar historial:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo cargar el historial de aprobaciones',
      life: 3000
    })
  } finally {
    loading.value = false
  }
}

const getStatusLabel = (status) => {
  const labels = {
    approved: 'Aprobado',
    rejected: 'Rechazado',
    observed: 'Observado'
  }
  return labels[status] || status
}

const getStatusSeverity = (status) => {
  const severities = {
    approved: 'success',
    rejected: 'danger',
    observed: 'warn'
  }
  return severities[status] || 'secondary'
}

const getStatusColor = (status) => {
  const colors = {
    approved: 'bg-green-500',
    rejected: 'bg-red-500',
    observed: 'bg-orange-500'
  }
  return colors[status] || 'bg-gray-500'
}

const getStatusIcon = (status) => {
  const icons = {
    approved: 'pi pi-check',
    rejected: 'pi pi-times',
    observed: 'pi pi-exclamation-triangle'
  }
  return icons[status] || 'pi pi-question'
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleDateString('es-PE', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatTime = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return date.toLocaleTimeString('es-PE', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const getRelativeTime = (dateString) => {
  if (!dateString) return '-'
  
  const date = new Date(dateString)
  const now = new Date()
  const diff = now - date
  
  const seconds = Math.floor(diff / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)
  const months = Math.floor(days / 30)
  const years = Math.floor(days / 365)
  
  if (years > 0) return `hace ${years} año${years > 1 ? 's' : ''}`
  if (months > 0) return `hace ${months} mes${months > 1 ? 'es' : ''}`
  if (days > 0) return `hace ${days} día${days > 1 ? 's' : ''}`
  if (hours > 0) return `hace ${hours} hora${hours > 1 ? 's' : ''}`
  if (minutes > 0) return `hace ${minutes} minuto${minutes > 1 ? 's' : ''}`
  return 'hace unos segundos'
}

const contarPorEstado = (status) => {
  return historial.value.filter(item => item.status === status).length
}

const exportarHistorial = () => {
  try {
    // Crear contenido CSV
    const headers = ['ID', 'Estado', 'Usuario', 'Comentario', 'Fecha', 'Hora']
    const rows = historial.value.map(item => [
      item.id,
      getStatusLabel(item.status),
      item.approved_by,
      item.comment || 'Sin comentario',
      formatDate(item.approved_at),
      formatTime(item.approved_at)
    ])
    
    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
    ].join('\n')
    
    // Crear blob y descargar
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
    const link = document.createElement('a')
    const url = URL.createObjectURL(blob)
    
    link.setAttribute('href', url)
    link.setAttribute('download', `historial_aprobaciones_${props.idPropiedad}_${Date.now()}.csv`)
    link.style.visibility = 'hidden'
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    toast.add({
      severity: 'success',
      summary: 'Exportado',
      detail: 'Historial exportado correctamente',
      life: 3000
    })
  } catch (error) {
    console.error('Error al exportar:', error)
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'No se pudo exportar el historial',
      life: 3000
    })
  }
}

const resetForm = () => {
  historial.value = []
}

const handleClose = () => {
  visible.value = false
}
</script>

<style scoped>
/* Animación suave para los items */
.transition-shadow {
  transition: box-shadow 0.3s ease;
}
</style>